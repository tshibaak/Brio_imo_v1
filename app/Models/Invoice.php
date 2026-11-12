<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\Money;

#[Fillable(['contract_id', 'number', 'period_start', 'period_end', 'issued_at', 'due_at', 'currency', 'status'])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'issued_at' => 'date', 'due_at' => 'date'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function totalAmount(): string
    {
        return Money::decimal(Money::sum($this->items()->pluck('amount')));
    }

    public function paidAmount(): string
    {
        return Money::decimal(Money::sum($this->payments()->where('status', 'approved')->pluck('amount')));
    }

    public function balance(): string
    {
        return Money::decimal(max(0, Money::minorUnits($this->totalAmount()) - Money::minorUnits($this->paidAmount())));
    }
}
