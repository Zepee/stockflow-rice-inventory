<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['sale_id', 'product_id', 'configured_selling_price', 'selling_price', 'override_reason', 'quantity', 'unit', 'line_subtotal'])]
#[Hidden(['override_reason'])]
class SaleItem extends Model
{
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movement(): HasOne
    {
        return $this->hasOne(InventoryMovement::class);
    }
}
