<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSellingPriceChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $actor): Product
    {
        return DB::transaction(function () use ($attributes, $actor): Product {
            $product = Product::query()->create($attributes);

            ProductSellingPriceChange::query()->create([
                'product_id' => $product->id,
                'old_price' => null,
                'new_price' => $attributes['selling_price'],
                'changed_by' => $actor->id,
                'changed_at' => now(),
            ]);

            return $product;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Product $product, array $attributes, User $actor): Product
    {
        return DB::transaction(function () use ($product, $attributes, $actor): Product {
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($product->unit !== $attributes['unit'] && $product->inventoryMovements()->exists()) {
                throw ValidationException::withMessages([
                    'unit' => 'Satuan dasar tidak dapat diubah setelah produk memiliki riwayat pergerakan stok.',
                ]);
            }

            $oldSellingPrice = (string) $product->selling_price;
            $newSellingPrice = (string) $attributes['selling_price'];

            $product->update($attributes);

            if ($oldSellingPrice !== $newSellingPrice) {
                ProductSellingPriceChange::query()->create([
                    'product_id' => $product->id,
                    'old_price' => $oldSellingPrice,
                    'new_price' => $newSellingPrice,
                    'changed_by' => $actor->id,
                    'changed_at' => now(),
                ]);
            }

            return $product->refresh();
        });
    }
}
