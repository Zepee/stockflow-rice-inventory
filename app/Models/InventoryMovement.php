<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['warehouse_id', 'product_id', 'stock_adjustment_item_id', 'purchase_item_id', 'sale_item_id', 'transaction_cancellation_id', 'reverses_movement_id', 'movement_type', 'quantity', 'balance_after', 'business_date', 'confirmed_at', 'created_by'])]
class InventoryMovement extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'balance_after' => 'decimal:3',
            'business_date' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adjustmentItem(): BelongsTo
    {
        return $this->belongsTo(StockAdjustmentItem::class, 'stock_adjustment_item_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function reversedMovement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_movement_id');
    }

    public function transactionCancellation(): BelongsTo
    {
        return $this->belongsTo(TransactionCancellation::class);
    }
}
