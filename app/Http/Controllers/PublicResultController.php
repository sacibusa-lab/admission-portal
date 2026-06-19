<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ExamScore;
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

        // Check if applicant has scores from multiple batches (resit history)
        $availableBatches = ExamScore::where('applicant_id', $applicant->id)
            ->whereNotNull('exam_batch')
            ->distinct()
            ->pluck('exam_batch')
            ->toArray();

        // Include applicant's current batch if they have scores in it
        $allBatches = collect($availableBatches);

        // Only show selector if there are multiple distinct batches
        if ($allBatches->count() > 1 || ($applicant->exam_batch && str_contains($applicant->exam_batch, 'Resit'))) {
            session(['result_available_batches' => $allBatches->values()->toArray()]);
            session()->forget('result_selected_batch');
        } else {
            session()->forget(['result_available_batches', 'result_selected_batch']);
        }

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

        // Check if batch selector is needed
        $availableBatches = session('result_available_batches', []);
        $selectedBatch = session('result_selected_batch');

        // Filter scores by selected batch if provided
        if ($selectedBatch && $applicant->examScores->isNotEmpty()) {
            $filteredScores = $applicant->examScores->filter(function ($score) use ($selectedBatch) {
                return $score->exam_batch === $selectedBatch || $score->exam_batch === null;
            });
            // Replace the relation collection with filtered scores for this request
            $applicant->setRelation('examScores', $filteredScores);
        }

        // Automatic Cutoff Check & Admission Status Update
        // Skip auto-status if the applicant has a resit batch — they're mid-resit cycle
        $hasResitBatch = $applicant->exam_batch && str_contains($applicant->exam_batch, 'Resit');

        if ($applicant->examScores->isNotEmpty() && !$hasResitBatch) {
            $averageScore = $applicant->examScores->avg('score');
            
            $class = strtoupper($applicant->class_applying_for);
            $isJunior = str_starts_with($class, 'JSS') || str_contains($class, 'JUNIOR');
            $isSenior = str_starts_with($class, 'SS') || str_contains($class, 'SENIOR');
            
            $juniorCutoff = intval(Setting::get('admission_junior_cutoff', 50));
            $seniorCutoff = intval(Setting::get('admission_senior_cutoff', 50));
            
            $cutoff = $isJunior ? $juniorCutoff : ($isSenior ? $seniorCutoff : 50);

            $passed = $averageScore >= $cutoff;

            // Keep the DB admission_status in sync with the dynamic cutoff result.
            // Only "Rejected" is considered final — "Failed" can be overridden if the cutoff changes.
            $expectedStatus = $passed ? 'Admitted' : 'Failed';

            if ($applicant->admission_status !== $expectedStatus && $applicant->admission_status !== 'Rejected') {
                $applicant->update([
                    'admission_status' => $expectedStatus
                ]);

                \App\Models\AdmissionHistory::create([
                    'applicant_id' => $applicant->id,
                    'status' => $expectedStatus,
                    'officer_id' => null,
                    'remarks' => ($passed
                        ? 'Admitted automatically by passing the entrance exam score cutoff of ' . $cutoff . '%'
                        : 'Marked as Failed - average score of ' . round($averageScore, 1) . '% is below the cutoff of ' . $cutoff . '%'),
                ]);

                \App\Helpers\AuditLogger::log($passed ? 'automatic_admission' : 'automatic_failure', [
                    'applicant_id' => $applicant->id,
                    'registration_number' => $applicant->registration_number,
                    'average_score' => $averageScore,
                    'cutoff_mark' => $cutoff,
                ], 0);
            }
        }

        return view('public.result_details', compact('applicant', 'availableBatches', 'selectedBatch'));
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
     * Store the selected batch in session and redirect to details.
     */
    public function selectBatch(Request $request)
    {
        $applicantId = session('verified_result_applicant_id');
        if (!$applicantId) {
            return redirect()->route('public.results.form');
        }

        $request->validate(['batch' => 'required|string']);
        session(['result_selected_batch' => $request->batch]);

        return redirect()->route('public.results.details');
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

        // 3. Batch and DB updates — keep old scores, mark them with current batch
        $currentBatch = $applicant->exam_batch ?: 'Batch A';

        // Generate the next resit batch name (e.g. Batch A → Batch A - Resit → Batch A - Resit 2)
        // Include this applicant's own batch when checking so we don't re-generate the same name
        $base = $currentBatch;
        $resitCount = 0;
        if (str_ends_with($currentBatch, ' - Resit')) {
            // Extract base by stripping the last ' - Resit' suffix and any trailing number
            if (preg_match('/^(.+?)(?: - Resit(?: (\d+))?)?$/', $currentBatch, $m)) {
                $base = trim($m[1]);
                $resitCount = isset($m[2]) ? (int)$m[2] : 1;
            }
            $resitCount++;
        }
        $newBatch = $base . ' - Resit' . ($resitCount > 1 ? ' ' . $resitCount : '');

        \Illuminate\Support\Facades\DB::transaction(function () use ($applicant, $currentBatch, $newBatch) {
            // Mark existing scores with the current batch name so they're preserved
            $applicant->examScores()
                ->whereNull('exam_batch')
                ->orWhere('exam_batch', $currentBatch)
                ->update(['exam_batch' => $currentBatch]);

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
