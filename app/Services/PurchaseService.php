<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Models\Warehouse;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    private const MAX_QUANTITY = '999999999999.999';

    private const MAX_MONEY = '18446744073709551615';

    public function __construct(private readonly TransactionNumberGenerator $numbers) {}

    /** @param array<string, mixed> $data @return array{lines: array<int, array<string, mixed>>, total_value: string|null, price_snapshots: array<string, string>} */
    public function preview(array $data, User $actor): array
    {
        $products = Product::query()
            ->whereIn('id', collect($data['items'])->pluck('product_id'))
            ->get(['id', 'name', 'sku', 'unit', 'purchase_price'])
            ->keyBy(fn (Product $product): string => (string) $product->id);

        $lines = [];
        $snapshots = [];
        $total = BigInteger::zero();

        foreach ($data['items'] as $index => $item) {
            $product = $products->get((string) $item['product_id']);
            if (! $product) {
                throw ValidationException::withMessages(["items.{$index}.product_id" => 'Produk tidak tersedia. Tinjau ulang transaksi.']);
            }

            $defaultPrice = (string) $product->purchase_price;
            $override = $actor->isOwner() ? ($item['purchase_price_override'] ?? null) : null;
            $appliedPrice = $override !== null && $override !== '' ? (string) $override : $defaultPrice;
            $quantity = BigDecimal::of((string) $item['quantity'])->toScale(3, RoundingMode::Unnecessary);
            $lineSubtotal = $this->lineSubtotal($appliedPrice, $quantity, $index);
            $total = $this->addMoney($total, BigInteger::of($lineSubtotal), $index);
            $snapshots[(string) $product->id] = $defaultPrice;

            $line = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'quantity' => (string) $quantity,
            ];

            if ($actor->isOwner()) {
                $line['default_purchase_price'] = $defaultPrice;
                $line['purchase_price'] = $appliedPrice;
                $line['line_subtotal'] = $lineSubtotal;
                $line['override_reason'] = $override !== null && $override !== '' ? trim((string) ($item['override_reason'] ?? '')) : null;
            }

            $lines[] = $line;
        }

        return [
            'lines' => $lines,
            'total_value' => $actor->isOwner() ? (string) $total : null,
            'price_snapshots' => $snapshots,
        ];
    }

    /** @param array<string, mixed> $data */
    public function confirm(array $data, string $submissionKey, User $actor): Purchase
    {
        $existing = Purchase::query()->where('submission_key', $submissionKey)->first();
        if ($existing) {
            return $existing;
        }

        $number = $this->numbers->allocate('purchase', 'PUR');
        $confirmedAt = now(config('app.timezone'));

        try {
            return DB::transaction(function () use ($data, $submissionKey, $actor, $number, $confirmedAt): Purchase {
                $warehouse = Warehouse::query()->where('is_default', true)->where('is_active', true)->firstOrFail();
                $purchase = Purchase::query()->create([
                    'transaction_number' => $number,
                    'submission_key' => $submissionKey,
                    'warehouse_id' => $warehouse->id,
                    'supplier_id' => $data['supplier_id'],
                    'business_date' => $data['business_date'],
                    'notes' => $data['notes'] ?? null,
                    'total_value' => 0,
                    'created_by' => $actor->id,
                    'confirmed_at' => $confirmedAt,
                    'status' => 'confirmed',
                ]);

                $items = collect($data['items'])->sortBy(fn (array $item) => (int) $item['product_id']);
                $total = BigInteger::zero();

                foreach ($items as $index => $item) {
                    $product = Product::query()->whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                    $defaultPrice = (string) $product->purchase_price;
                    $reviewedDefault = $data['price_snapshots'][(string) $product->id] ?? null;

                    if ($reviewedDefault !== $defaultPrice) {
                        throw ValidationException::withMessages([
                            'purchase' => "Harga beli default {$product->name} berubah setelah ditinjau. Silakan tinjau ulang transaksi.",
                        ]);
                    }

                    $override = $actor->isOwner() ? ($item['purchase_price_override'] ?? null) : null;
                    $appliedPrice = $override !== null && $override !== '' ? (string) $override : $defaultPrice;
                    $quantity = BigDecimal::of((string) $item['quantity'])->toScale(3, RoundingMode::Unnecessary);
                    $lineSubtotal = $this->lineSubtotal($appliedPrice, $quantity, $index);
                    $total = $this->addMoney($total, BigInteger::of($lineSubtotal), $index);

                    $before = BigDecimal::of((string) $product->current_stock)->toScale(3, RoundingMode::Unnecessary);
                    $after = $before->plus($quantity);
                    if ($after->isGreaterThan(BigDecimal::of(self::MAX_QUANTITY))) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => "Saldo {$product->name} melebihi kapasitas stok yang didukung.",
                        ]);
                    }

                    $purchaseItem = PurchaseItem::query()->create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $product->id,
                        'default_purchase_price' => $defaultPrice,
                        'purchase_price' => $appliedPrice,
                        'override_reason' => $override !== null && $override !== '' ? trim((string) ($item['override_reason'] ?? '')) : null,
                        'quantity' => (string) $quantity,
                        'unit' => $product->unit,
                        'line_subtotal' => $lineSubtotal,
                    ]);

                    $product->current_stock = (string) $after->toScale(3, RoundingMode::Unnecessary);
                    $product->save();

                    InventoryMovement::query()->create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'purchase_item_id' => $purchaseItem->id,
                        'movement_type' => 'purchase',
                        'quantity' => (string) $quantity,
                        'balance_after' => (string) $after->toScale(3, RoundingMode::Unnecessary),
                        'business_date' => $data['business_date'],
                        'confirmed_at' => $confirmedAt,
                        'created_by' => $actor->id,
                    ]);
                }

                $purchase->update(['total_value' => (string) $total]);

                return $purchase->load([
                    'supplier:id,name',
                    'creator:id,name',
                    'items.product:id,name,sku,unit',
                ]);
            });
        } catch (QueryException $exception) {
            $duplicate = Purchase::query()->where('submission_key', $submissionKey)->first();
            if ($duplicate) {
                return $duplicate;
            }

            throw $exception;
        }
    }

    private function lineSubtotal(string $unitPrice, BigDecimal $quantity, int|string $index): string
    {
        $subtotal = BigDecimal::of($unitPrice)->multipliedBy($quantity)->toScale(0, RoundingMode::HalfUp)->toBigInteger();

        if ($subtotal->isGreaterThan(BigInteger::of(self::MAX_MONEY))) {
            throw ValidationException::withMessages(["items.{$index}.quantity" => 'Subtotal baris melebihi batas nilai transaksi yang didukung.']);
        }

        return (string) $subtotal;
    }

    private function addMoney(BigInteger $total, BigInteger $lineSubtotal, int|string $index): BigInteger
    {
        $next = $total->plus($lineSubtotal);
        if ($next->isGreaterThan(BigInteger::of(self::MAX_MONEY))) {
            throw ValidationException::withMessages(["items.{$index}.quantity" => 'Total pembelian melebihi batas nilai transaksi yang didukung.']);
        }

        return $next;
    }
}
