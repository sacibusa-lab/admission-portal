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

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
