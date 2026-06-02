<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\AdmissionHistory;
use App\Models\Setting;
use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\DB;

class AdmissionService
{
    /**
     * Generate the next unique registration number.
     * Uses the configured registration prefix and auto-increments based on the database.
     */
    public function generateRegistrationNumber(): string
    {
        $prefix = Setting::get('admission_prefix', 'SAC');
        
        // Fetch the maximum applicant ID to increment from, including soft deleted ones to avoid conflicts.
        $nextId = (Applicant::withTrashed()->max('id') ?? 0) + 1;
        
        return $prefix . '-' . sprintf('%04d', $nextId);
    }

    /**
     * Update the admission status of an applicant and record in status history.
     */
    public function changeStatus(Applicant $applicant, string $status, ?string $remarks, int $officerId): void
    {
        DB::transaction(function () use ($applicant, $status, $remarks, $officerId) {
            $oldStatus = $applicant->admission_status;
            
            $applicant->update([
                'admission_status' => $status,
                'updated_by' => $officerId
            ]);

            // Save history
            AdmissionHistory::create([
                'applicant_id' => $applicant->id,
                'status' => $status,
                'officer_id' => $officerId,
                'remarks' => $remarks
            ]);

            // Audit Log
            AuditLogger::log('status_change', [
                'applicant_id' => $applicant->id,
                'registration_number' => $applicant->registration_number,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'remarks' => $remarks
            ], $officerId);
        });
    }
}
