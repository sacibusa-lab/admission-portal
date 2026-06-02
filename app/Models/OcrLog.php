<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrLog extends Model
{
    protected $fillable = [
        'file_path',
        'response_data',
        'extracted_fields',
        'user_id',
        'status'
    ];

    protected $casts = [
        'response_data' => 'array',
        'extracted_fields' => 'array',
    ];

    const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
