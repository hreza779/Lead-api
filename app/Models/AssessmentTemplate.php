<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'estimated_time',
        'status',
        'created_by',
    ];

    public function steps()
    {
        return $this->hasMany(AssessmentStep::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
