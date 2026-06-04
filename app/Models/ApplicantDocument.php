<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantDocument extends Model
{
    protected $fillable = [
        'applicant_id',
        'document_type',
        'file_path',
        'file_name',
        'file_size',
        'extracted_data'
    ];

    protected $casts = [
        'extracted_data' => 'array',
    ];

    /**
     * Get the document file URL.
     */
    public function getFileUrlAttribute(): string
    {
        if (!$this->file_path) {
            return '#';
        }
        if (str_starts_with($this->file_path, 'uploads/')) {
            return asset($this->file_path);
        }
        if (file_exists(public_path($this->file_path))) {
            return asset($this->file_path);
        }
        return asset('storage/' . $this->file_path);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
