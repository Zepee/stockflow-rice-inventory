<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['transaction_number', 'submission_key', 'warehouse_id', 'customer_id', 'business_date', 'notes', 'total_value', 'created_by', 'confirmed_at', 'status'])]
#[Hidden(['total_value'])]
class Sale extends Model
{
    protected function casts(): array
    {
        return ['business_date' => 'date', 'confirmed_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(TransactionCancellation::class);
    }
}
