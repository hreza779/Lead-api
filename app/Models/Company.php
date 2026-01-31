<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'phone',
        'email',
        'address',
        'national_id',
        'economic_code',
        'field_of_activity',
        'logo',
        'website',
        'description',
        'owner_id',
        'status',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function managers()
    {
        return $this->hasMany(Manager::class);
    }
}
