<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Models\Warehouse;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    private const MAX_QUANTITY = '999999999999.999';

    public function __construct(private readonly TransactionNumberGenerator $numbers) {}

    /** @param array{business_date: string, reason: string, submission_key: string, items: array<int, array{product_id: int|string, direction: string, quantity: string}>} $data */
    public function confirm(array $data, User $actor): StockAdjustment
    {
        $existing = StockAdjustment::query()->where('submission_key', $data['submission_key'])->first();
        if ($existing) {
            return $existing;
        }

        $number = $this->numbers->allocate('adjustment', 'ADJ');
        $confirmedAt = now(config('app.timezone'));

        try {
            return DB::transaction(function () use ($data, $actor, $number, $confirmedAt): StockAdjustment {
                $warehouse = Warehouse::query()->where('is_default', true)->where('is_active', true)->firstOrFail();

                $adjustment = StockAdjustment::query()->create([
                    'transaction_number' => $number,
                    'submission_key' => $data['submission_key'],
                    'warehouse_id' => $warehouse->id,
                    'business_date' => $data['business_date'],
                    'reason' => trim($data['reason']),
                    'created_by' => $actor->id,
                    'confirmed_at' => $confirmedAt,
                    'status' => 'confirmed',
                ]);

                $items = collect($data['items'])->sortBy(fn (array $item) => (int) $item['product_id']);

                foreach ($items as $index => $item) {
                    $product = Product::query()->whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                    $quantity = BigDecimal::of((string) $item['quantity'])->toScale(3, RoundingMode::Unnecessary);
                    $before = BigDecimal::of((string) $product->current_stock)->toScale(3, RoundingMode::Unnecessary);
                    $after = $item['direction'] === 'increase' ? $before->plus($quantity) : $before->minus($quantity);

                    if ($after->isNegative()) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => "Stok {$product->name} tidak cukup untuk dikurangi sebanyak {$quantity}.",
                        ]);
                    }

                    if ($after->isGreaterThan(BigDecimal::of(self::MAX_QUANTITY))) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => "Saldo {$product->name} melebihi kapasitas stok yang didukung.",
                        ]);
                    }

                    $line = $adjustment->items()->create([
                        'product_id' => $product->id,
                        'direction' => $item['direction'],
                        'quantity' => (string) $quantity,
                        'unit' => $product->unit,
                    ]);

                    $signedQuantity = $item['direction'] === 'increase' ? $quantity : $quantity->negated();
                    $product->current_stock = (string) $after->toScale(3, RoundingMode::Unnecessary);
                    $product->save();

                    InventoryMovement::query()->create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'stock_adjustment_item_id' => $line->id,
                        'movement_type' => 'adjustment',
                        'quantity' => (string) $signedQuantity->toScale(3, RoundingMode::Unnecessary),
                        'balance_after' => (string) $after->toScale(3, RoundingMode::Unnecessary),
                        'business_date' => $data['business_date'],
                        'confirmed_at' => $confirmedAt,
                        'created_by' => $actor->id,
                    ]);
                }

                return $adjustment->load(['creator:id,name', 'items.product:id,name,sku,unit']);
            });
        } catch (QueryException $exception) {
            $duplicate = StockAdjustment::query()->where('submission_key', $data['submission_key'])->first();
            if ($duplicate) {
                return $duplicate;
            }

            throw $exception;
        }
    }
}

