<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AcademicSession;
use App\Models\ExamSubject;
use App\Models\ExamScore;
use App\Models\Setting;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamScoreController extends Controller
{
    /**
     * Display a listing of exam subjects.
     */
    public function subjectsIndex()
    {
        $subjects = ExamSubject::withCount('scores')->orderBy('name', 'asc')->get();
        return view('exams.subjects', compact('subjects'));
    }

    /**
     * Store a new exam subject.
     */
    public function subjectsStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:exam_subjects,name',
        ]);

        $subject = ExamSubject::create([
            'name' => trim($request->name),
        ]);

        AuditLogger::log('create_exam_subject', ['subject_id' => $subject->id, 'name' => $subject->name]);

        return redirect()->route('exams.subjects')->with('success', "Exam subject '{$subject->name}' created successfully.");
    }

    /**
     * Delete an exam subject.
     */
    public function subjectsDestroy($id)
    {
        $subject = ExamSubject::findOrFail($id);
        
        // Prevent deletion if scores are already recorded
        if ($subject->scores()->exists()) {
            return redirect()->route('exams.subjects')->with('error', "Cannot delete subject '{$subject->name}' because scores are already recorded for it.");
        }

        $subject->delete();

        AuditLogger::log('delete_exam_subject', ['subject_id' => $id, 'name' => $subject->name]);

        return redirect()->route('exams.subjects')->with('success', "Exam subject deleted successfully.");
    }

    /**
     * Show the batch score entry sheet.
     */
    public function scoresIndex(Request $request)
    {
        $subjects = ExamSubject::orderBy('name', 'asc')->get();
        
        $defaultClass = \App\Models\SchoolClass::orderBy('name', 'asc')->first()?->name ?? 'JSS1';
        $selectedClass = $request->input('class', $defaultClass);
        $selectedBatch = $request->input('batch', 'Batch A');
        
        // Active session
        $currentSessionId = Setting::get('admission_current_session_id');
        if (empty($currentSessionId)) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            $currentSessionId = $currentSession ? $currentSession->id : 1;
        }
        
        // Get all applicants for active session, class & batch
        $applicants = Applicant::where('academic_session_id', $currentSessionId)
            ->where('class_applying_for', $selectedClass)
            ->where('exam_batch', $selectedBatch)
            ->orderBy('surname', 'asc')
            ->orderBy('first_name', 'asc')
            ->get();
            
        // Load existing scores for the applicants
        $scores = ExamScore::whereIn('applicant_id', $applicants->pluck('id'))->get();
        
        // Build applicant_id => [subject_id => score] nested map
        $scoresMap = [];
        foreach ($scores as $score) {
            $scoresMap[$score->applicant_id][$score->exam_subject_id] = $score->score;
        }
        
        return view('exams.scores', compact('subjects', 'selectedClass', 'selectedBatch', 'applicants', 'scoresMap'));
    }

    /**
     * Store batch scores.
     */
    public function scoresStore(Request $request)
    {
        $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'required|array',
            'scores.*.*' => 'nullable|integer|min:0|max:100',
        ]);

        $scoresData = $request->scores; // applicant_id => [subject_id => score]

        DB::transaction(function () use ($scoresData) {
            foreach ($scoresData as $applicantId => $subjectScores) {
                // Ensure applicant exists
                if (!Applicant::where('id', $applicantId)->exists()) {
                    continue;
                }

                foreach ($subjectScores as $subjectId => $score) {
                    // Ensure subject exists
                    if (!ExamSubject::where('id', $subjectId)->exists()) {
                        continue;
                    }

                    if ($score === null || $score === '') {
                        // Delete score entry if empty
                        ExamScore::where('exam_subject_id', $subjectId)
                            ->where('applicant_id', $applicantId)
                            ->delete();
                    } else {
                        // Update or create score
                        ExamScore::updateOrCreate(
                            [
                                'exam_subject_id' => $subjectId,
                                'applicant_id' => $applicantId
                            ],
                            [
                                'score' => intval($score)
                            ]
                        );
                    }
                }
            }
        });

        AuditLogger::log('save_batch_exam_scores', [
            'total_students_updated' => count($scoresData)
        ]);

        return redirect()->back()->with('success', "Exam scores updated successfully.");
    }

    /**
     * Export exam scores for class as CSV.
     */
    public function exportScoresCsv(Request $request)
    {
        $request->validate([
            'class' => 'required|string',
            'batch' => 'nullable|string',
        ]);

        $class = $request->class;
        $batch = $request->input('batch', 'Batch A');
        $subjects = ExamSubject::orderBy('name', 'asc')->get();

        $currentSessionId = Setting::get('admission_current_session_id');
        if (empty($currentSessionId)) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            $currentSessionId = $currentSession ? $currentSession->id : 1;
        }

        $applicants = Applicant::where('academic_session_id', $currentSessionId)
            ->where('class_applying_for', $class)
            ->where('exam_batch', $batch)
            ->orderBy('surname', 'asc')
            ->orderBy('first_name', 'asc')
            ->get();

        $scores = ExamScore::whereIn('applicant_id', $applicants->pluck('id'))->get();
        
        $scoresMap = [];
        foreach ($scores as $score) {
            $scoresMap[$score->applicant_id][$score->exam_subject_id] = $score->score;
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="exam_scores_' . strtolower($class) . '_' . strtolower(str_replace(' ', '_', $batch)) . '.csv"',
        ];

        $callback = function () use ($applicants, $scoresMap, $subjects, $class, $batch) {
            $file = fopen('php://output', 'w');
            
            // Build CSV Header row
            $headerRow = ['S/N', 'Registration Number', 'Surname', 'Firstname', 'Other Name', 'Class', 'Exam Batch'];
            foreach ($subjects as $sub) {
                $headerRow[] = $sub->name;
            }
            $headerRow[] = 'Average (%)';
            
            fputcsv($file, $headerRow);

            foreach ($applicants as $index => $app) {
                $row = [
                    $index + 1,
                    $app->registration_number,
                    $app->surname,
                    $app->first_name,
                    $app->other_name,
                    $class,
                    $batch
                ];

                $totalScore = 0;
                $subjectsGraded = 0;
                foreach ($subjects as $sub) {
                    $score = isset($scoresMap[$app->id][$sub->id]) ? $scoresMap[$app->id][$sub->id] : null;
                    if ($score !== null) {
                        $row[] = $score;
                        $totalScore += $score;
                        $subjectsGraded++;
                    } else {
                        $row[] = '-';
                    }
                }

                $row[] = $subjectsGraded > 0 ? round($totalScore / $subjectsGraded, 1) : '-';
                
                fputcsv($file, $row);
            }
            fclose($file);
        };

        AuditLogger::log('export_exam_scores_csv', ['class' => $class, 'batch' => $batch]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export exam scores for class as PDF.
     */
    public function exportScoresPdf(Request $request)
    {
        $request->validate([
            'class' => 'required|string',
            'batch' => 'nullable|string',
        ]);

        $class = $request->class;
        $batch = $request->input('batch', 'Batch A');
        $subjects = ExamSubject::orderBy('name', 'asc')->get();

        $currentSessionId = Setting::get('admission_current_session_id');
        $currentSessionName = '2025/2026';
        if (empty($currentSessionId)) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            $currentSessionId = $currentSession ? $currentSession->id : 1;
            $currentSessionName = $currentSession ? $currentSession->name : '2025/2026';
        } else {
            $currentSessionName = AcademicSession::find($currentSessionId)?->name ?? '2025/2026';
        }

        $applicants = Applicant::where('academic_session_id', $currentSessionId)
            ->where('class_applying_for', $class)
            ->where('exam_batch', $batch)
            ->orderBy('surname', 'asc')
            ->orderBy('first_name', 'asc')
            ->get();

        $scores = ExamScore::whereIn('applicant_id', $applicants->pluck('id'))->get();
        
        $scoresMap = [];
        foreach ($scores as $score) {
            $scoresMap[$score->applicant_id][$score->exam_subject_id] = $score->score;
        }

        $schoolName = Setting::get('school_name', "St. Augustine's College, Ibusa");
        $schoolAddress = Setting::get('school_address', 'Ibusa, Delta State, Nigeria');

        $html = view('exams.scores_pdf', compact('applicants', 'subjects', 'scoresMap', 'class', 'batch', 'currentSessionName', 'schoolName', 'schoolAddress'))->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

        AuditLogger::log('export_exam_scores_pdf', ['class' => $class, 'batch' => $batch]);

        return $pdf->download('exam_scores_' . strtolower($class) . '_' . strtolower(str_replace(' ', '_', $batch)) . '.pdf');
    }
}

