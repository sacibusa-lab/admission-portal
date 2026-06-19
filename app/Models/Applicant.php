<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'registration_number',
        'surname',
        'first_name',
        'other_name',
        'gender',
        'date_of_birth',
        'state_of_origin',
        'lga',
        'nationality',
        'parent_phone_number',
        'email',
        'address',
        'class_applying_for',
        'admission_status',
        'exam_batch',
        'academic_session_id',
        'passport_path',
        'birth_certificate_path',
        'school_result_path',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get applicant's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->surname} {$this->first_name} {$this->other_name}");
    }

    /**
     * Get applicant's passport URL.
     */
    public function getPassportUrlAttribute(): string
    {
        if (!$this->passport_path) {
            return '';
        }
        if (str_starts_with($this->passport_path, 'uploads/')) {
            return asset($this->passport_path);
        }
        if (file_exists(public_path($this->passport_path))) {
            return asset($this->passport_path);
        }
        return asset('storage/' . $this->passport_path);
    }

    /**
     * Get applicant's birth certificate URL.
     */
    public function getBirthCertificateUrlAttribute(): string
    {
        if (!$this->birth_certificate_path) {
            return '#';
        }
        if (str_starts_with($this->birth_certificate_path, 'uploads/')) {
            return asset($this->birth_certificate_path);
        }
        if (file_exists(public_path($this->birth_certificate_path))) {
            return asset($this->birth_certificate_path);
        }
        return asset('storage/' . $this->birth_certificate_path);
    }

    /**
     * Get applicant's school result URL.
     */
    public function getSchoolResultUrlAttribute(): string
    {
        if (!$this->school_result_path) {
            return '#';
        }
        if (str_starts_with($this->school_result_path, 'uploads/')) {
            return asset($this->school_result_path);
        }
        if (file_exists(public_path($this->school_result_path))) {
            return asset($this->school_result_path);
        }
        return asset('storage/' . $this->school_result_path);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AdmissionHistory::class)->orderBy('created_at', 'desc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function examScores(): HasMany
    {
        return $this->hasMany(ExamScore::class);
    }

    // ── Cutoff-based admission helpers ──

    /**
     * Get the total exam score across all subjects.
     */
    public function getTotalExamScoreAttribute(): int
    {
        return (int) ($this->exam_scores_sum_score ?? $this->examScores()->sum('score'));
    }

    /**
     * Determine whether this applicant meets the cutoff mark
     * based on their class (Junior vs Senior) and configured cutoff settings.
     */
    public function getMeetsCutoffAttribute(): bool
    {
        $cutoff = self::getCutoffForClass($this->class_applying_for);
        return $this->total_exam_score >= $cutoff;
    }

    /**
     * Get the appropriate cutoff mark for a given class name.
     */
    public static function getCutoffForClass(string $class): int
    {
        $prefix = strtoupper(substr(trim($class), 0, 3));
        if ($prefix === 'JSS') {
            return (int) \App\Models\Setting::get('admission_junior_cutoff', 50);
        }
        // Default to senior cutoff for SS, Nursery, Primary, or unknown
        return (int) \App\Models\Setting::get('admission_senior_cutoff', 50);
    }
}
