<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSession extends Model
{
    protected $fillable = ['name', 'is_current'];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }
}
