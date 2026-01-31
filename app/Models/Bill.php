<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'company_id',
        'bill_number',
        'total_amount',
        'currency',
        'status',
        'due_date',
        'paid_date',
        'official_invoice_requested',
        'official_invoice_pdf_url',
        'description',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'official_invoice_requested' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function financialDocuments(): BelongsToMany
    {
        return $this->belongsToMany(FinancialDocument::class, 'bill_items')
            ->withTimestamps();
    }
}
