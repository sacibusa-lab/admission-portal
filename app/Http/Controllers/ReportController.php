<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AdmissionHistory;
use App\Models\Applicant;
use App\Models\Setting;
use App\Services\ReportService;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display admission dashboard metrics and summary indicators.
     */
    public function index()
    {
        $classStats = $this->reportService->getApplicantsByClass();
        $monthStats = $this->reportService->getApplicantsByMonth();
        $statusStats = $this->reportService->getStatusSummary();
        $smsStats = $this->reportService->getSmsUsage();
        $successStats = $this->reportService->getSuccessRate();
        $genderStats = $this->reportService->getGenderBreakdown();
        $overviewStats = $this->reportService->getOverviewStats();
        $sessions = AcademicSession::orderBy('name', 'desc')->get();
        $recentApplicants = Applicant::with('academicSession')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('reports.index', compact(
            'classStats', 'monthStats', 'statusStats', 'smsStats',
            'successStats', 'genderStats', 'overviewStats',
            'sessions', 'recentApplicants'
        ));
    }

    /**
     * Export filtered list of applicants as a CSV spreadsheet.
     */
    public function exportCsv(Request $request)
    {
        $query = Applicant::with('academicSession');

        // Apply filters
        if ($request->filled('class')) {
            $query->where('class_applying_for', $request->class);
        }
        if ($request->filled('status')) {
            $query->where('admission_status', $request->status);
        }
        if ($request->filled('session')) {
            $query->where('academic_session_id', $request->session);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applicants = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Registration Number', 'Surname', 'Firstname', 'Other Name', 'Gender', 
            'Class Applied', 'Academic Session', 'Parent Phone', 'Email Address', 
            'Admission Status', 'Date Registered'
        ];

        $rows = [];
        foreach ($applicants as $app) {
            $rows[] = [
                $app->registration_number,
                $app->surname,
                $app->first_name,
                $app->other_name,
                $app->gender,
                $app->class_applying_for,
                $app->academicSession?->name ?? '',
                $app->parent_phone_number,
                $app->email,
                $app->admission_status,
                $app->created_at->format('Y-m-d H:i:s')
            ];
        }

        AuditLogger::log('export_reports_csv', ['records_count' => count($rows)]);

        return $this->reportService->exportCsv($headers, $rows, 'applicants_report_' . date('Ymd_His') . '.csv');
    }

    /**
     * Export filtered list of applicants as an A4 Landscape PDF document.
     */
    public function exportPdf(Request $request)
    {
        $query = Applicant::with('academicSession');

        if ($request->filled('class')) {
            $query->where('class_applying_for', $request->class);
        }
        if ($request->filled('status')) {
            $query->where('admission_status', $request->status);
        }
        if ($request->filled('session')) {
            $query->where('academic_session_id', $request->session);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applicants = $query->orderBy('created_at', 'desc')->get();
        
        $schoolName = Setting::get('school_name', "St. Augustine's College, Ibusa");
        $schoolAddress = Setting::get('school_address', 'Ibusa, Delta State, Nigeria');
        
        $filterDetails = [
            'class' => $request->class ?? 'All Classes',
            'status' => $request->status ?? 'All Statuses',
            'session' => $request->session ? optional(AcademicSession::find($request->session))->name : 'All Sessions',
            'date_from' => $request->date_from ?? '',
            'date_to' => $request->date_to ?? '',
        ];

        $html = view('reports.pdf', compact('applicants', 'schoolName', 'schoolAddress', 'filterDetails'))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        AuditLogger::log('export_reports_pdf', ['records_count' => $applicants->count()]);

        return $pdf->download('applicants_report_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Bulk-evaluate all applicants against the current cutoff marks.
     * Updates admission_status to "Admitted" or "Failed" based on total exam score.
     */
    public function evaluateCutoff(Request $request)
    {
        $juniorCutoff = (int) Setting::get('admission_junior_cutoff', 50);
        $seniorCutoff = (int) Setting::get('admission_senior_cutoff', 50);
        $officerId = auth()->id();

        $applicants = Applicant::withSum('examScores', 'score')->get();
        $admitted = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($applicants as $app) {
                $totalScore = (int) ($app->exam_scores_sum_score ?? 0);
                
                // Applicants with no exam scores are skipped
                if ($app->examScores()->count() === 0) {
                    $skipped++;
                    continue;
                }

                $cutoff = str_starts_with($app->class_applying_for, 'JSS') ? $juniorCutoff : $seniorCutoff;
                $newStatus = $totalScore >= $cutoff ? 'Admitted' : 'Failed';
                $oldStatus = $app->admission_status;

                // Skip if already at the target status
                if ($oldStatus === $newStatus) {
                    $skipped++;
                    continue;
                }

                $app->update([
                    'admission_status' => $newStatus,
                    'updated_by' => $officerId,
                ]);

                AdmissionHistory::create([
                    'applicant_id' => $app->id,
                    'status' => $newStatus,
                    'officer_id' => $officerId,
                    'remarks' => "Auto-evaluated by cutoff (Score: {$totalScore}, Cutoff: {$cutoff}%)",
                ]);

                if ($newStatus === 'Admitted') {
                    $admitted++;
                } else {
                    $failed++;
                }
            }

            DB::commit();

            AuditLogger::log('evaluate_cutoff', [
                'admitted' => $admitted,
                'failed' => $failed,
                'skipped' => $skipped,
                'junior_cutoff' => $juniorCutoff,
                'senior_cutoff' => $seniorCutoff,
            ], $officerId);

            return response()->json([
                'success' => true,
                'message' => "Evaluation complete: {$admitted} admitted, {$failed} failed, {$skipped} unchanged.",
                'admitted' => $admitted,
                'failed' => $failed,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Evaluation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
