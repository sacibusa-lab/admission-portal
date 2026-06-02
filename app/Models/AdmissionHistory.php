<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionHistory extends Model
{
    protected $table = 'admission_histories';

    protected $fillable = [
        'applicant_id',
        'status',
        'officer_id',
        'remarks'
    ];

    // Disable updated_at, keep created_at
    const UPDATED_AT = null;

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
