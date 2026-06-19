<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Applicant;
use App\Models\Setting;
use App\Services\ReportService;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
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
}
