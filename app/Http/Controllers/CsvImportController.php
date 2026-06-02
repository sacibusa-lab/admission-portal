<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AcademicSession;
use App\Models\Setting;
use App\Services\AdmissionService;
use App\Services\SmsService;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CsvImportController extends Controller
{
    protected AdmissionService $admissionService;
    protected SmsService $smsService;

    public function __construct(AdmissionService $admissionService, SmsService $smsService)
    {
        $this->admissionService = $admissionService;
        $this->smsService = $smsService;
    }

    /**
     * Display CSV import dashboard.
     */
    public function showImportForm()
    {
        return view('applicants.import');
    }

    /**
     * Handle bulk CSV registrations.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'The uploaded file is empty.');
        }

        // Standardize header strings (remove potential BOM markers or white spaces)
        $header = array_map(function($h) {
            return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
        }, $header);

        $requiredHeaders = ['Surname', 'Firstname', 'ParentPhone', 'Class'];
        foreach ($requiredHeaders as $req) {
            if (!in_array($req, $header)) {
                fclose($handle);
                return back()->with('error', "Missing required column header: '{$req}'.");
            }
        }

        $headerMap = array_flip($header);

        // Fetch active academic session
        $currentSessionId = Setting::get('admission_current_session_id');
        if (empty($currentSessionId)) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            $currentSessionId = $currentSession ? $currentSession->id : 1;
        }

        $validClasses = \App\Models\SchoolClass::pluck('name')->toArray();

        $successful = 0;
        $failed = 0;
        $duplicates = 0;
        $errors = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), '');
            }

            $surname = trim($row[$headerMap['Surname']] ?? '');
            $firstname = trim($row[$headerMap['Firstname']] ?? '');
            $parentPhone = trim($row[$headerMap['ParentPhone']] ?? '');
            $class = strtoupper(trim($row[$headerMap['Class']] ?? ''));
            $examBatch = 'Batch A';
            if (isset($headerMap['ExamBatch'])) {
                $val = trim($row[$headerMap['ExamBatch']] ?? '');
                if ($val !== '') {
                    $examBatch = $val;
                }
            }

            // Row validation
            $rowErrors = [];
            if (empty($surname)) $rowErrors[] = 'Surname is missing.';
            if (empty($firstname)) $rowErrors[] = 'Firstname is missing.';
            if (empty($parentPhone)) $rowErrors[] = 'ParentPhone number is missing.';
            if (!in_array($class, $validClasses)) {
                $rowErrors[] = 'Class must be one of: ' . implode(', ', $validClasses) . '.';
            }
            if (strlen($examBatch) > 50) {
                $rowErrors[] = 'ExamBatch name must not exceed 50 characters.';
            }

            if (!empty($rowErrors)) {
                $failed++;
                $errors[] = [
                    'row' => $rowCount,
                    'surname' => $surname,
                    'firstname' => $firstname,
                    'parent_phone' => $parentPhone,
                    'class' => $class,
                    'reason' => implode(' ', $rowErrors)
                ];
                continue;
            }

            // Skip Duplicates Check (Surname + Firstname + ParentPhone combo)
            $isDuplicate = Applicant::where('surname', $surname)
                ->where('first_name', $firstname)
                ->where('parent_phone_number', $parentPhone)
                ->where('academic_session_id', $currentSessionId)
                ->exists();

            if ($isDuplicate) {
                $duplicates++;
                $failed++;
                $errors[] = [
                    'row' => $rowCount,
                    'surname' => $surname,
                    'firstname' => $firstname,
                    'parent_phone' => $parentPhone,
                    'class' => $class,
                    'reason' => 'Skipped: Duplicate applicant record.'
                ];
                continue;
            }

            // Try creating applicant
            try {
                $regNumber = $this->admissionService->generateRegistrationNumber();
                
                $applicant = null;
                DB::transaction(function () use ($surname, $firstname, $parentPhone, $class, $examBatch, $regNumber, $currentSessionId, &$applicant) {
                    $applicant = Applicant::create([
                        'registration_number' => $regNumber,
                        'surname' => $surname,
                        'first_name' => $firstname,
                        'gender' => 'Male', // Default placeholder
                        'date_of_birth' => now()->subYears(10)->format('Y-m-d'), // Default placeholder
                        'state_of_origin' => 'Delta', // School location state
                        'lga' => 'Oshimili North', // School LGA location
                        'nationality' => 'Nigerian',
                        'parent_phone_number' => $parentPhone,
                        'address' => 'Ibusa, Delta State', // School area
                        'class_applying_for' => $class,
                        'admission_status' => 'Pending',
                        'exam_batch' => $examBatch,
                        'academic_session_id' => $currentSessionId,
                        'created_by' => Auth::id()
                    ]);

                    // Add to timeline
                    $applicant->histories()->create([
                        'status' => 'Pending',
                        'officer_id' => Auth::id(),
                        'remarks' => 'Applicant imported via CSV batch upload.'
                    ]);
                });

                // Auto Send SMS notification (queued)
                $smsMessage = "Dear {$firstname},\n\nThank you for registering for admission into St. Augustine's College, Ibusa.\n\nYour Registration Number is:\n\n{$regNumber}\n\nKeep this number safe.\n\nAdmission Office";
                $this->smsService->queue($parentPhone, $smsMessage);

                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowCount,
                    'surname' => $surname,
                    'firstname' => $firstname,
                    'parent_phone' => $parentPhone,
                    'class' => $class,
                    'reason' => 'Database error: ' . $e->getMessage()
                ];
            }
        }

        fclose($handle);

        // Store error details in the session
        Session::put('csv_import_errors', $errors);

        AuditLogger::log('csv_import', [
            'total_rows_evaluated' => $rowCount,
            'successful_count' => $successful,
            'failed_count' => $failed,
            'duplicate_skips' => $duplicates
        ]);

        return redirect()->route('applicants.import')->with([
            'import_summary' => [
                'total' => $rowCount,
                'successful' => $successful,
                'failed' => $failed,
                'has_errors' => count($errors) > 0
            ]
        ]);
    }

    /**
     * Download the detailed CSV error log of the last import.
     */
    public function downloadErrorReport()
    {
        $errors = Session::get('csv_import_errors', []);
        if (empty($errors)) {
            return redirect()->route('applicants.import')->with('error', 'No recent failure reports available.');
        }

        $callback = function () use ($errors) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['CSV Row No', 'Surname', 'Firstname', 'ParentPhone', 'Class', 'Failure Reasons']);

            foreach ($errors as $err) {
                fputcsv($file, [
                    $err['row'],
                    $err['surname'],
                    $err['firstname'],
                    $err['parent_phone'],
                    $err['class'],
                    $err['reason']
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="csv_import_failures.csv"',
        ]);
    }

    /**
     * Download the sample CSV template.
     */
    public function downloadSample()
    {
        $filePath = base_path('sample_applicants.csv');
        if (file_exists($filePath)) {
            return response()->download($filePath, 'sample_applicants_template.csv');
        }
        return back()->with('error', 'Sample template file not found.');
    }
}
