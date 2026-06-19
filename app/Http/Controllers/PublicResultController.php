<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PublicResultController extends Controller
{
    /**
     * Show result checking form page.
     */
    public function showForm()
    {
        return view('public.check_result');
    }

    /**
     * Process checking credentials.
     */
    public function check(Request $request)
    {
        $request->validate([
            'registration_number' => 'required|string',
        ]);

        $regNo = trim($request->registration_number);

        // Retrieve applicant matching reg number
        $applicant = Applicant::where('registration_number', $regNo)
            ->first();

        if (!$applicant) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No candidate found matching the provided Registration Number. Please try again.');
        }

        // Store applicant ID in session to allow secure access to details
        session(['verified_result_applicant_id' => $applicant->id]);

        return redirect()->route('public.results.details');
    }

    /**
     * Show verified candidate details.
     */
    public function showDetails()
    {
        $applicantId = session('verified_result_applicant_id');
        if (!$applicantId) {
            return redirect()->route('public.results.form')
                ->with('error', 'Please enter your credentials to check your result.');
        }

        $applicant = Applicant::with(['academicSession', 'examScores.subject'])->findOrFail($applicantId);

        // Automatic Cutoff Check & Admission Status Update
        if ($applicant->examScores->isNotEmpty()) {
            $averageScore = $applicant->examScores->avg('score');
            
            $class = strtoupper($applicant->class_applying_for);
            $isJunior = str_starts_with($class, 'JSS') || str_contains($class, 'JUNIOR');
            $isSenior = str_starts_with($class, 'SS') || str_contains($class, 'SENIOR');
            
            $juniorCutoff = intval(Setting::get('admission_junior_cutoff', 50));
            $seniorCutoff = intval(Setting::get('admission_senior_cutoff', 50));
            
            $cutoff = $isJunior ? $juniorCutoff : ($isSenior ? $seniorCutoff : 50);
            
            if ($averageScore >= $cutoff) {
                // Only promote if not already admitted, rejected, or explicitly failed
                if (!in_array($applicant->admission_status, ['Admitted', 'Rejected', 'Failed'])) {
                    $applicant->update([
                        'admission_status' => 'Admitted'
                    ]);

                    // Add history log
                    \App\Models\AdmissionHistory::create([
                        'applicant_id' => $applicant->id,
                        'status' => 'Admitted',
                        'officer_id' => null,
                        'remarks' => 'Admitted automatically by passing the entrance exam score cutoff of ' . $cutoff . '%'
                    ]);

                    // Log audit
                    \App\Helpers\AuditLogger::log('automatic_admission', [
                        'applicant_id' => $applicant->id,
                        'registration_number' => $applicant->registration_number,
                        'average_score' => $averageScore,
                        'cutoff_mark' => $cutoff
                    ], 0);
                }
            }
        }

        return view('public.result_details', compact('applicant'));
    }

    /**
     * Public download of Admission Letter for admitted candidates.
     */
    public function downloadLetter($id)
    {
        $applicantId = session('verified_result_applicant_id');
        if (!$applicantId || intval($applicantId) !== intval($id)) {
            abort(403, 'Unauthorized access to admission letter.');
        }

        $applicant = Applicant::with(['academicSession', 'examScores'])->findOrFail($id);

        if (!$applicant->passesCutoff()) {
            abort(403, 'Admission letter is only available for candidates who meet the cutoff mark.');
        }

        // Load settings and build admission letter content
        $letterContent = Setting::get('admission_letter_template');
        $letterContent = str_replace(
            ['{firstname}', '{surname}', '{registration_number}', '{class}', '{session}'],
            [$applicant->first_name, $applicant->surname, $applicant->registration_number, $applicant->class_applying_for, $applicant->academicSession->name],
            $letterContent
        );

        $schoolName = Setting::get('school_name', "St. Augustine's College, Ibusa");
        $schoolAddress = Setting::get('school_address', 'Ibusa, Delta State, Nigeria');
        $schoolEmail = Setting::get('school_email', 'info@staugustineibusa.com');
        $schoolPhone = Setting::get('school_phone', '+2348030000000');

        $html = view('letters.pdf', compact('applicant', 'letterContent', 'schoolName', 'schoolAddress', 'schoolEmail', 'schoolPhone'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->download('Admission_Letter_' . $applicant->registration_number . '.pdf');
    }

    /**
     * Public registration for a resit exam.
     */
    public function registerResit($id)
    {
        // 1. Verify session
        $applicantId = session('verified_result_applicant_id');
        if (!$applicantId || intval($applicantId) !== intval($id)) {
            abort(403, 'Unauthorized access to resit registration.');
        }

        $applicant = Applicant::with('examScores')->findOrFail($id);

        // 2. Double check if they actually failed to prevent abuse
        $scores = $applicant->examScores;
        if ($scores->isEmpty()) {
            return redirect()->back()->with('error', 'No exam scores recorded for this candidate.');
        }

        $averageScore = $scores->avg('score');
        $class = strtoupper($applicant->class_applying_for);
        $isJunior = str_starts_with($class, 'JSS') || str_contains($class, 'JUNIOR');
        $isSenior = str_starts_with($class, 'SS') || str_contains($class, 'SENIOR');
        $juniorCutoff = intval(Setting::get('admission_junior_cutoff', 50));
        $seniorCutoff = intval(Setting::get('admission_senior_cutoff', 50));
        $cutoff = $isJunior ? $juniorCutoff : ($isSenior ? $seniorCutoff : 50);

        if ($averageScore >= $cutoff) {
            return redirect()->back()->with('error', 'Only candidates who failed the cutoff mark can register for a resit.');
        }

        // 3. Batch and DB updates
        $currentBatch = $applicant->exam_batch ?: 'Batch A';
        $newBatch = str_ends_with($currentBatch, ' - Resit') ? $currentBatch : $currentBatch . ' - Resit';

        \Illuminate\Support\Facades\DB::transaction(function () use ($applicant, $currentBatch, $newBatch) {
            // Delete existing scores
            $applicant->examScores()->delete();

            // Update applicant batch and reset status to Pending
            $applicant->update([
                'exam_batch' => $newBatch,
                'admission_status' => 'Pending'
            ]);

            // Save history log
            \App\Models\AdmissionHistory::create([
                'applicant_id' => $applicant->id,
                'status' => 'Pending',
                'officer_id' => null,
                'remarks' => "Registered for resit via public result page. Exam batch updated from '{$currentBatch}' to '{$newBatch}'."
            ]);
        });

        // 4. Send SMS Notification
        $smsService = app(\App\Services\SmsService::class);
        $smsMessage = "Dear {$applicant->first_name},\n\nYou have successfully registered for an entrance exam Resit.\n\nYour new Exam Batch is: {$newBatch}.\n\nPlease check back for schedule details.\n\nAdmission Office";
        $smsService->queue($applicant->parent_phone_number, $smsMessage);

        \App\Helpers\AuditLogger::log('public_resit_registration', [
            'applicant_id' => $applicant->id,
            'old_batch' => $currentBatch,
            'new_batch' => $newBatch
        ], 0);

        return redirect()->route('public.results.details')
            ->with('success', "You have successfully registered for a resit exam. Your new batch is: {$newBatch}.");
    }
}
