<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contract_id', 'uploaded_by', 'category', 'path', 'original_name', 'mime_type', 'size'])]
class ContractDocument extends Model
{
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
