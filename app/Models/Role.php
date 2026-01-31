<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'permissions',
        'description',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];
}
