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
}
