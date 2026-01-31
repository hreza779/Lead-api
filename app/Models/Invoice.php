<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'payment_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'request_date',
        'generated_date',
        'pdf_url',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_date' => 'date',
        'generated_date' => 'date',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
