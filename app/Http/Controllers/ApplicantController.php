<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AcademicSession;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\AuditLog;
use App\Services\AdmissionService;
use App\Services\SmsService;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\SchoolClass;

class ApplicantController extends Controller
{
    protected AdmissionService $admissionService;
    protected SmsService $smsService;

    public function __construct(AdmissionService $admissionService, SmsService $smsService)
    {
        $this->admissionService = $admissionService;
        $this->smsService = $smsService;
    }

    /**
     * Display a listing of applicants with search and filter capabilities.
     */
    public function index(Request $request)
    {
        $query = Applicant::with('academicSession');

        // Apply Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('other_name', 'like', "%{$search}%")
                  ->orWhere('parent_phone_number', 'like', "%{$search}%");
            });
        }

        // Apply Filters
        if ($request->filled('class')) {
            $query->where('class_applying_for', $request->class);
        }

        if ($request->filled('status')) {
            $query->where('admission_status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Performance Requirement: Pagination
        $applicants = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('applicants.index', compact('applicants'));
    }

    /**
     * Show the form for creating a new applicant.
     */
    public function create()
    {
        return view('applicants.create');
    }

    /**
     * Store a newly created applicant.
     */
    public function store(Request $request)
    {
        // Security Requirement: Validate all uploads
        $request->validate([
            'surname' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'other_name' => 'nullable|string|max:100',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'state_of_origin' => 'required|string|max:100',
            'lga' => 'required|string|max:100',
            'nationality' => 'required|string|max:100',
            'parent_phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'required|string',
            'class_applying_for' => [
                'required',
                Rule::in(SchoolClass::pluck('name')->toArray())
            ],
            'exam_batch' => 'nullable|string|max:50',
            'passport' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
            'birth_certificate' => 'required|file|mimes:pdf,jpeg,png,jpg|max:5120', // Max 5MB
            'school_result' => 'required|file|mimes:pdf,jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        $currentSessionId = Setting::get('admission_current_session_id');
        if (empty($currentSessionId)) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            $currentSessionId = $currentSession ? $currentSession->id : 1;
        }

        // Generate reg number
        $regNumber = $this->admissionService->generateRegistrationNumber();

        // Handle File Uploads (organized structure direct to public/uploads)
        $passportFile = $request->file('passport');
        $passportSize = $passportFile->getSize();
        $passportOrigName = $passportFile->getClientOriginalName();
        $passportName = 'passport_' . time() . '_' . uniqid() . '.' . $passportFile->getClientOriginalExtension();
        $passportFile->move(public_path('uploads/photos'), $passportName);
        $passportPath = 'uploads/photos/' . $passportName;

        $birthCertFile = $request->file('birth_certificate');
        $birthCertSize = $birthCertFile->getSize();
        $birthCertOrigName = $birthCertFile->getClientOriginalName();
        $birthCertName = 'birth_cert_' . time() . '_' . uniqid() . '.' . $birthCertFile->getClientOriginalExtension();
        $birthCertFile->move(public_path('uploads/documents'), $birthCertName);
        $birthCertPath = 'uploads/documents/' . $birthCertName;

        $resultFile = $request->file('school_result');
        $resultSize = $resultFile->getSize();
        $resultOrigName = $resultFile->getClientOriginalName();
        $resultName = 'school_result_' . time() . '_' . uniqid() . '.' . $resultFile->getClientOriginalExtension();
        $resultFile->move(public_path('uploads/documents'), $resultName);
        $resultPath = 'uploads/documents/' . $resultName;

        DB::transaction(function () use ($request, $regNumber, $currentSessionId, $passportPath, $birthCertPath, $resultPath, $passportSize, $passportOrigName, $birthCertSize, $birthCertOrigName, $resultSize, $resultOrigName, &$applicant) {
            $applicant = Applicant::create([
                'registration_number' => $regNumber,
                'surname' => $request->surname,
                'first_name' => $request->first_name,
                'other_name' => $request->other_name,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'state_of_origin' => $request->state_of_origin,
                'lga' => $request->lga,
                'nationality' => $request->nationality,
                'parent_phone_number' => $request->parent_phone_number,
                'email' => $request->email,
                'address' => $request->address,
                'class_applying_for' => $request->class_applying_for,
                'admission_status' => 'Pending',
                'exam_batch' => $request->input('exam_batch', 'Batch A') ?: 'Batch A',
                'academic_session_id' => $currentSessionId,
                'passport_path' => $passportPath,
                'birth_certificate_path' => $birthCertPath,
                'school_result_path' => $resultPath,
                'created_by' => Auth::id()
            ]);

            // Save initial status history
            $applicant->histories()->create([
                'status' => 'Pending',
                'officer_id' => Auth::id(),
                'remarks' => 'Initial applicant registration.'
            ]);

            // Save documents in applicant_documents table
            $applicant->documents()->create([
                'document_type' => 'passport',
                'file_path' => $passportPath,
                'file_name' => $passportOrigName,
                'file_size' => $passportSize
            ]);

            $applicant->documents()->create([
                'document_type' => 'birth_certificate',
                'file_path' => $birthCertPath,
                'file_name' => $birthCertOrigName,
                'file_size' => $birthCertSize
            ]);

            $applicant->documents()->create([
                'document_type' => 'previous_result',
                'file_path' => $resultPath,
                'file_name' => $resultOrigName,
                'file_size' => $resultSize
            ]);
        });

        // Trigger SMS notification automatically (queued)
        $smsMessage = "Dear {$applicant->first_name},\n\nThank you for registering for admission into St. Augustine's College, Ibusa.\n\nYour Registration Number is:\n\n{$regNumber}\n\nKeep this number safe.\n\nAdmission Office";
        
        // Dispatch SMS to parent phone number
        $this->smsService->queue($applicant->parent_phone_number, $smsMessage);

        AuditLogger::log('register_applicant', [
            'applicant_id' => $applicant->id,
            'registration_number' => $regNumber
        ]);

        return redirect()->route('applicants.show', $applicant->id)
            ->with('success', "Applicant registered successfully. Registration No: {$regNumber}");
    }

    /**
     * Display the applicant profile.
     */
    public function show($id)
    {
        $applicant = Applicant::with(['academicSession', 'documents', 'histories.officer', 'creator', 'updater', 'examScores'])
            ->findOrFail($id);

        // Fetch logs associated with this applicant
        $smsLogs = SmsLog::where('phone', $applicant->parent_phone_number)
            ->orderBy('created_at', 'desc')
            ->get();

        $auditLogs = AuditLog::where('details->applicant_id', $applicant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('applicants.show', compact('applicant', 'smsLogs', 'auditLogs'));
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $applicant = Applicant::findOrFail($id);
        return view('applicants.edit', compact('applicant'));
    }

    /**
     * Update applicant details.
     */
    public function update(Request $request, $id)
    {
        $applicant = Applicant::findOrFail($id);

        $request->validate([
            'surname' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'other_name' => 'nullable|string|max:100',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'state_of_origin' => 'required|string|max:100',
            'lga' => 'required|string|max:100',
            'nationality' => 'required|string|max:100',
            'parent_phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'required|string',
            'class_applying_for' => [
                'required',
                Rule::in(SchoolClass::pluck('name')->toArray())
            ],
            'exam_batch' => 'nullable|string|max:50',
            'passport' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'birth_certificate' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'school_result' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
        ]);

        $updateData = $request->only([
            'surname', 'first_name', 'other_name', 'gender', 'date_of_birth',
            'state_of_origin', 'lga', 'nationality',
            'parent_phone_number', 'email', 'address', 'class_applying_for', 'exam_batch'
        ]);
        $updateData['updated_by'] = Auth::id();

        // Handle uploads
        if ($request->hasFile('passport')) {
            if ($applicant->passport_path && file_exists(public_path($applicant->passport_path))) {
                @unlink(public_path($applicant->passport_path));
            }
            $file = $request->file('passport');
            $fileSize = $file->getSize();
            $origName = $file->getClientOriginalName();
            $filename = 'passport_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/photos'), $filename);
            $passportPath = 'uploads/photos/' . $filename;
            
            $updateData['passport_path'] = $passportPath;
            $applicant->documents()->create([
                'document_type' => 'passport',
                'file_path' => $passportPath,
                'file_name' => $origName,
                'file_size' => $fileSize
            ]);
        }

        if ($request->hasFile('birth_certificate')) {
            if ($applicant->birth_certificate_path && file_exists(public_path($applicant->birth_certificate_path))) {
                @unlink(public_path($applicant->birth_certificate_path));
            }
            $file = $request->file('birth_certificate');
            $fileSize = $file->getSize();
            $origName = $file->getClientOriginalName();
            $filename = 'birth_cert_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/documents'), $filename);
            $birthCertPath = 'uploads/documents/' . $filename;
            
            $updateData['birth_certificate_path'] = $birthCertPath;
            $applicant->documents()->create([
                'document_type' => 'birth_certificate',
                'file_path' => $birthCertPath,
                'file_name' => $origName,
                'file_size' => $fileSize
            ]);
        }

        if ($request->hasFile('school_result')) {
            if ($applicant->school_result_path && file_exists(public_path($applicant->school_result_path))) {
                @unlink(public_path($applicant->school_result_path));
            }
            $file = $request->file('school_result');
            $fileSize = $file->getSize();
            $origName = $file->getClientOriginalName();
            $filename = 'school_result_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/documents'), $filename);
            $resultPath = 'uploads/documents/' . $filename;
            
            $updateData['school_result_path'] = $resultPath;
            $applicant->documents()->create([
                'document_type' => 'previous_result',
                'file_path' => $resultPath,
                'file_name' => $origName,
                'file_size' => $fileSize
            ]);
        }

        $applicant->update($updateData);

        AuditLogger::log('update_applicant', [
            'applicant_id' => $applicant->id,
            'registration_number' => $applicant->registration_number
        ]);

        return redirect()->route('applicants.show', $applicant->id)->with('success', 'Applicant updated successfully.');
    }

    /**
     * Register applicant for a resit exam.
     */
    public function registerResit($id)
    {
        $applicant = Applicant::findOrFail($id);

        $currentBatch = $applicant->exam_batch ?: 'Batch A';
        $newBatch = str_ends_with($currentBatch, ' - Resit') ? $currentBatch : $currentBatch . ' - Resit';

        DB::transaction(function () use ($applicant, $currentBatch, $newBatch) {
            // Delete existing scores
            $applicant->examScores()->delete();

            // Update applicant batch and reset status to Pending
            $applicant->update([
                'exam_batch' => $newBatch,
                'admission_status' => 'Pending'
            ]);

            // Save history log
            $applicant->histories()->create([
                'status' => 'Pending',
                'officer_id' => Auth::id(),
                'remarks' => "Registered for resit. Exam batch updated from '{$currentBatch}' to '{$newBatch}'."
            ]);
        });

        // Queue SMS notification
        $smsMessage = "Dear {$applicant->first_name},\n\nYou have been registered for an entrance exam Resit.\n\nYour new Exam Batch is: {$newBatch}.\n\nPlease check back for schedule details.\n\nAdmission Office";
        $this->smsService->queue($applicant->parent_phone_number, $smsMessage);

        AuditLogger::log('applicant_register_resit', [
            'applicant_id' => $applicant->id,
            'old_batch' => $currentBatch,
            'new_batch' => $newBatch
        ]);

        return redirect()->route('applicants.show', $applicant->id)
            ->with('success', "Applicant registered for resit successfully under {$newBatch}.");
    }

    /**
     * Print registration slip.
     */
    public function printSlip($id)
    {
        $applicant = Applicant::with('academicSession')->findOrFail($id);
        return view('applicants.slip', compact('applicant'));
    }

    /**
     * Delete/archive applicant profile.
     */
    public function destroy($id)
    {
        $applicant = Applicant::findOrFail($id);

        $applicant->delete();

        AuditLogger::log('delete_applicant', [
            'applicant_id' => $applicant->id,
            'registration_number' => $applicant->registration_number,
            'full_name' => $applicant->full_name
        ]);

        return redirect()->route('applicants.index')
            ->with('success', "Applicant profile deleted successfully.");
    }
    

    /**
     * Provide search suggestions for live autocomplete.
     */
    public function searchSuggestions(Request $request)
    {
        $term = $request->query('term');
        if (!$term) {
            return response()->json([]);
        }
        $applicants = Applicant::where('registration_number', 'like', "%{$term}%")
            ->orWhere('full_name', 'like', "%{$term}%")
            ->orWhere('parent_phone_number', 'like', "%{$term}%")
            ->limit(5)
            ->get(['id', 'full_name', 'registration_number']);
        return response()->json($applicants);
    }
}
