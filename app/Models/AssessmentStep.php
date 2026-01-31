<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentStep extends Model
{
    protected $fillable = [
        'template_id',
        'title',
        'description',
        'order',
    ];

    public function template()
    {
        return $this->belongsTo(AssessmentTemplate::class);
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class, 'step_id');
    }
}
