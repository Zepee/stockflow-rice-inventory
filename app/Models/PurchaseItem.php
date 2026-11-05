<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_id', 'product_id', 'default_purchase_price', 'purchase_price', 'override_reason', 'quantity', 'unit', 'line_subtotal'])]
#[Hidden(['default_purchase_price', 'purchase_price', 'override_reason', 'line_subtotal'])]
class PurchaseItem extends Model
{
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InventoryMovement::class);
    }
}
