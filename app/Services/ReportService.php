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
     * Get admission success rate based on cutoff marks.
     * "Admitted" = applicant's total exam score ≥ class-appropriate cutoff.
     * Changes dynamically when cutoff marks are updated in settings.
     */
    public function getSuccessRate(): array
    {
        $total = Applicant::count();
        $juniorCutoff = (int) \App\Models\Setting::get('admission_junior_cutoff', 50);
        $seniorCutoff = (int) \App\Models\Setting::get('admission_senior_cutoff', 50);

        $meetsCutoff = Applicant::withSum('examScores', 'score')
            ->get()
            ->filter(function ($app) use ($juniorCutoff, $seniorCutoff) {
                $cutoff = str_starts_with($app->class_applying_for, 'JSS') ? $juniorCutoff : $seniorCutoff;
                return ($app->exam_scores_sum_score ?? 0) >= $cutoff;
            })
            ->count();

        $successRate = $total > 0 ? round(($meetsCutoff / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'admitted' => $meetsCutoff,
            'rate' => $successRate,
            'junior_cutoff' => $juniorCutoff,
            'senior_cutoff' => $seniorCutoff,
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
     * 'admitted' is dynamically computed from cutoff marks.
     */
    public function getOverviewStats(): array
    {
        $juniorCutoff = (int) \App\Models\Setting::get('admission_junior_cutoff', 50);
        $seniorCutoff = (int) \App\Models\Setting::get('admission_senior_cutoff', 50);

        $meetsCutoff = Applicant::withSum('examScores', 'score')
            ->get()
            ->filter(function ($app) use ($juniorCutoff, $seniorCutoff) {
                $cutoff = str_starts_with($app->class_applying_for, 'JSS') ? $juniorCutoff : $seniorCutoff;
                return ($app->exam_scores_sum_score ?? 0) >= $cutoff;
            })
            ->count();

        return [
            'total_applicants' => Applicant::count(),
            'admitted' => $meetsCutoff,
            'junior_cutoff' => $juniorCutoff,
            'senior_cutoff' => $seniorCutoff,
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
