<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamScore extends Model
{
    protected $fillable = ['applicant_id', 'exam_subject_id', 'score'];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class, 'exam_subject_id');
    }
}
