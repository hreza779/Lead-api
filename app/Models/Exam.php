<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'description',
        'duration',
        'passing_score',
        'status',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('exam_questions.order');
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class, 'exam_managers')
            ->withPivot('assigned_date', 'due_date', 'status', 'attempts', 'max_attempts')
            ->withTimestamps();
    }

    public function examSets(): BelongsToMany
    {
        return $this->belongsToMany(ExamSet::class, 'exam_set_items')
            ->withPivot('order', 'status')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
