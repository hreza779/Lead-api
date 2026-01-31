<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExamSet extends Model
{
    protected $fillable = [
        'manager_id',
        'assessment_id',
        'title',
        'description',
        'assigned_date',
        'exam_date',
        'due_date',
        'status',
        'username',
        'password',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'exam_date' => 'date',
        'due_date' => 'date',
    ];

    protected $hidden = [
        'password',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExamSetItem::class);
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_set_items')
            ->withPivot('order', 'status')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
