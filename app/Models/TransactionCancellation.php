<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_number', 'purchase_id', 'sale_id', 'stock_adjustment_id', 'reason', 'cancelled_by', 'confirmed_at'])]
class TransactionCancellation extends Model
{
    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
