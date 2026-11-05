<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\TransactionCancellation;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionCancellationService
{
    private const MAX_QUANTITY = '999999999999.999';

    public function __construct(private readonly TransactionNumberGenerator $numbers) {}

    public function cancel(string $type, int|string $id, string $reason, User $actor): TransactionCancellation
    {
        abort_unless($actor->isOwner(), 403);

        [$model, $sourceId] = match ($type) {
            'purchase' => [Purchase::class, 'purchase_id'],
            'sale' => [Sale::class, 'sale_id'],
            'adjustment' => [StockAdjustment::class, 'stock_adjustment_id'],
            default => abort(404),
        };

        $number = $this->numbers->allocate('reversal', 'REV');
        $confirmedAt = now(config('app.timezone'));

        return DB::transaction(function () use ($model, $sourceId, $id, $reason, $actor, $number, $confirmedAt): TransactionCancellation {
            $source = $model::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $existing = TransactionCancellation::query()->where($sourceId, $source->id)->first();
            if ($existing) {
                return $existing;
            }

            if ($source->status !== 'confirmed') {
                throw ValidationException::withMessages(['transaction' => 'Transaksi ini tidak dalam status yang dapat dibatalkan.']);
            }

            $movements = $this->sourceMovements($source)->sortBy('product_id')->values();
            if ($movements->isEmpty()) {
                throw ValidationException::withMessages(['transaction' => 'Movement stok transaksi tidak ditemukan. Pembatalan tidak dapat diproses.']);
            }

            $productIds = $movements->pluck('product_id')->unique()->sort()->values()->all();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $cancellation = TransactionCancellation::query()->create([
                'transaction_number' => $number,
                $sourceId => $source->id,
                'reason' => trim($reason),
                'cancelled_by' => $actor->id,
                'confirmed_at' => $confirmedAt,
            ]);

            foreach ($movements as $movement) {
                /** @var Product $product */
                $product = $products->get($movement->product_id);
                if (! $product) {
                    throw ValidationException::withMessages(['transaction' => 'Produk pada transaksi tidak ditemukan.']);
                }

                $before = BigDecimal::of((string) $product->current_stock)->toScale(3, RoundingMode::Unnecessary);
                $reversal = BigDecimal::of((string) $movement->quantity)->negated()->toScale(3, RoundingMode::Unnecessary);
                $after = $before->plus($reversal)->toScale(3, RoundingMode::Unnecessary);
                if ($after->isNegative()) {
                    throw ValidationException::withMessages([
                        'transaction' => "Pembatalan ditolak karena stok {$product->name} sudah terpakai dan saldo akan menjadi negatif.",
                    ]);
                }
                if ($after->isGreaterThan(BigDecimal::of(self::MAX_QUANTITY))) {
                    throw ValidationException::withMessages(['transaction' => "Saldo {$product->name} melebihi kapasitas stok yang didukung."]);
                }

                $product->current_stock = (string) $after;
                $product->save();

                InventoryMovement::query()->create([
                    'warehouse_id' => $movement->warehouse_id,
                    'product_id' => $movement->product_id,
                    'transaction_cancellation_id' => $cancellation->id,
                    'reverses_movement_id' => $movement->id,
                    'movement_type' => 'reversal',
                    'quantity' => (string) $reversal,
                    'balance_after' => (string) $after,
                    'business_date' => $confirmedAt->toDateString(),
                    'confirmed_at' => $confirmedAt,
                    'created_by' => $actor->id,
                ]);
            }

            $source->update(['status' => 'cancelled']);

            return $cancellation->load('authorizer:id,name');
        });
    }

    private function sourceMovements(Purchase|Sale|StockAdjustment $source): \Illuminate\Support\Collection
    {
        $source->loadMissing('items');

        return InventoryMovement::query()
            ->where(function ($query) use ($source) {
                match (true) {
                    $source instanceof Purchase => $query->whereIn('purchase_item_id', $source->items->pluck('id')),
                    $source instanceof Sale => $query->whereIn('sale_item_id', $source->items->pluck('id')),
                    default => $query->whereIn('stock_adjustment_item_id', $source->items->pluck('id')),
                };
            })
            ->get();
    }
}
