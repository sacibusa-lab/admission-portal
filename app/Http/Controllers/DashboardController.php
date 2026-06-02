<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\SmsLog;
use App\Models\AuditLog;
use App\Services\ReportService;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display the admin/officer/principal dashboard.
     */
    public function index()
    {
        // Performance Requirement: Cache dashboard statistics
        $stats = Cache::remember('dashboard_stats', 60, function () {
            return [
                'total_applicants' => Applicant::count(),
                'applicants_today' => Applicant::whereDate('created_at', today())->count(),
                'pending_admissions' => Applicant::where('admission_status', 'Pending')->count(),
                'admitted_students' => Applicant::where('admission_status', 'Admitted')->count(),
                'rejected_students' => Applicant::where('admission_status', 'Rejected')->count(),
                'sms_sent_today' => SmsLog::whereDate('created_at', today())->where('status', 'like', 'Sent%')->count(),
            ];
        });

        // Load chart datasets
        $classData = $this->reportService->getApplicantsByClass();
        $monthData = $this->reportService->getApplicantsByMonth();

        // Load recent activity logs
        $recentActivities = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact('stats', 'classData', 'monthData', 'recentActivities'));
    }
}
