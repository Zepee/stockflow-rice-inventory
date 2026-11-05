<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sku', 'description', 'unit', 'purchase_price', 'selling_price', 'minimum_stock', 'is_active'])]
#[Hidden(['purchase_price'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'minimum_stock' => 'decimal:3',
            'current_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function sellingPriceChanges(): HasMany
    {
        return $this->hasMany(ProductSellingPriceChange::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
