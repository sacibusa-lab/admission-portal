<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\SmsLog;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Get counts of applicants by class applying for.
     */
    public function getApplicantsByClass(): array
    {
        return Applicant::select('class_applying_for', DB::raw('count(*) as total'))
            ->groupBy('class_applying_for')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get registration counts by month.
     */
    public function getApplicantsByMonth(): array
    {
        // Using cross-database safe grouping
        $results = Applicant::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_key"),
            DB::raw("count(*) as total")
        )
            ->groupBy('month_key')
            ->orderBy('month_key', 'asc')
            ->get();

        $formatted = [];
        foreach ($results as $row) {
            if ($row->month_key) {
                // Get month name from key e.g. 2026-06 -> June 2026
                $date = \DateTime::createFromFormat('Y-m', $row->month_key);
                $formatted[] = [
                    'label' => $date ? $date->format('F Y') : $row->month_key,
                    'total' => $row->total
                ];
            }
        }
        return $formatted;
    }

    /**
     * Get admission success rate based on evaluated admission statuses.
     * "Admitted" counts applicants whose status was set to "Admitted"
     * by the Evaluate Cutoff action (Pass/Fail → Admitted/Failed).
     */
    public function getSuccessRate(): array
    {
        $total = Applicant::count();
        $admitted = Applicant::where('admission_status', 'Admitted')->count();
        $successRate = $total > 0 ? round(($admitted / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'admitted' => $admitted,
            'rate' => $successRate,
            'junior_cutoff' => (int) \App\Models\Setting::get('admission_junior_cutoff', 50),
            'senior_cutoff' => (int) \App\Models\Setting::get('admission_senior_cutoff', 50),
        ];
    }

    /**
     * Get SMS logs usage stats.
     */
    public function getSmsUsage(): array
    {
        return SmsLog::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    /**
     * Get counts of applicants by current admission status.
     */
    public function getStatusSummary(): array
    {
        return Applicant::select('admission_status', DB::raw('count(*) as total'))
            ->groupBy('admission_status')
            ->get()
            ->toArray();
    }

    /**
     * Get gender distribution of applicants.
     */
    public function getGenderBreakdown(): array
    {
        return Applicant::select('gender', DB::raw('count(*) as total'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get()
            ->toArray();
    }

    /**
     * Get overview counts for stats cards.
     * 'admitted' reflects the admission_status set by Evaluate Cutoff.
     */
    public function getOverviewStats(): array
    {
        return [
            'total_applicants' => Applicant::count(),
            'admitted' => Applicant::where('admission_status', 'Admitted')->count(),
            'failed' => Applicant::where('admission_status', 'Failed')->count(),
            'junior_cutoff' => (int) \App\Models\Setting::get('admission_junior_cutoff', 50),
            'senior_cutoff' => (int) \App\Models\Setting::get('admission_senior_cutoff', 50),
            'pending' => Applicant::whereIn('admission_status', ['Pending', 'Under Review'])->count(),
            'rejected' => Applicant::where('admission_status', 'Rejected')->count(),
            'exam_scheduled' => Applicant::where('admission_status', 'Exam Scheduled')->count(),
        ];
    }

    /**
     * Generate a StreamedResponse for CSV downloads.
     */
    public function exportCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, (array)$row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ]);
    }
}
