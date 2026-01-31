<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FinancialDocument extends Model
{
    protected $fillable = [
        'payment_id',
        'title',
        'amount',
        'currency',
        'type',
        'status',
        'description',
        'created_date',
        'paid_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_date' => 'date',
        'paid_date' => 'date',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function bills(): BelongsToMany
    {
        return $this->belongsToMany(Bill::class, 'bill_items')
            ->withTimestamps();
    }
}
