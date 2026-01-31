<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSetItem extends Model
{
    protected $fillable = [
        'exam_set_id',
        'exam_id',
        'order',
        'status',
    ];

    public function examSet(): BelongsTo
    {
        return $this->belongsTo(ExamSet::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
