<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['landlord_id', 'property_id', 'contract_id', 'ticket_id', 'category', 'description', 'amount', 'currency', 'spent_at', 'receipt_path'])]
class Expense extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['spent_at' => 'date'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
