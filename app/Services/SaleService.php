<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    private const MAX_QUANTITY = '999999999999.999';

    private const MAX_MONEY = '18446744073709551615';

    public function __construct(private readonly TransactionNumberGenerator $numbers) {}

    /** @param array<string, mixed> $data @return array{lines: array<int, array<string, mixed>>, total_value: string, price_snapshots: array<string, string>, stock_snapshots: array<string, string>} */
    public function preview(array $data, User $actor): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('id', collect($data['items'])->pluck('product_id'))
            ->get(['id', 'name', 'sku', 'unit', 'selling_price', 'current_stock'])
            ->keyBy(fn (Product $product): string => (string) $product->id);

        $lines = [];
        $priceSnapshots = [];
        $stockSnapshots = [];
        $total = BigInteger::zero();

        foreach ($data['items'] as $index => $item) {
            $product = $products->get((string) $item['product_id']);
            if (! $product) {
                throw ValidationException::withMessages(["items.{$index}.product_id" => 'Produk tidak tersedia. Tinjau ulang transaksi.']);
            }

            $quantity = BigDecimal::of((string) $item['quantity'])->toScale(3, RoundingMode::Unnecessary);
            $available = BigDecimal::of((string) $product->current_stock)->toScale(3, RoundingMode::Unnecessary);
            $enoughStock = ! $quantity->isGreaterThan($available);

            $configuredPrice = (string) $product->selling_price;
            $override = $actor->isOwner() ? ($item['selling_price_override'] ?? null) : null;
            $appliedPrice = $override !== null && $override !== '' ? (string) $override : $configuredPrice;
            $lineSubtotal = $this->lineSubtotal($appliedPrice, $quantity, $index);
            $total = $this->addMoney($total, BigInteger::of($lineSubtotal), $index);
            $priceSnapshots[(string) $product->id] = $configuredPrice;
            $stockSnapshots[(string) $product->id] = (string) $available;

            $lines[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'quantity' => (string) $quantity,
                'available_stock' => (string) $available,
                'remaining_stock' => $enoughStock ? (string) $available->minus($quantity)->toScale(3, RoundingMode::Unnecessary) : null,
                'enough_stock' => $enoughStock,
                'configured_selling_price' => $configuredPrice,
                'selling_price' => $appliedPrice,
                'line_subtotal' => $lineSubtotal,
                'override_reason' => $override !== null && $override !== '' ? trim((string) ($item['override_reason'] ?? '')) : null,
            ];
        }

        return [
            'lines' => $lines,
            'total_value' => (string) $total,
            'price_snapshots' => $priceSnapshots,
            'stock_snapshots' => $stockSnapshots,
        ];
    }

    /** @param array<string, mixed> $data */
    public function confirm(array $data, string $submissionKey, User $actor): Sale
    {
        $existing = Sale::query()->where('submission_key', $submissionKey)->first();
        if ($existing) {
            return $existing;
        }

        $number = $this->numbers->allocate('sale', 'SAL');
        $confirmedAt = now(config('app.timezone'));

        try {
            return DB::transaction(function () use ($data, $submissionKey, $actor, $number, $confirmedAt): Sale {
                $warehouse = Warehouse::query()->where('is_default', true)->where('is_active', true)->firstOrFail();
                $sale = Sale::query()->create([
                    'transaction_number' => $number,
                    'submission_key' => $submissionKey,
                    'warehouse_id' => $warehouse->id,
                    'customer_id' => $data['customer_id'],
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
                    if (! $product->is_active) {
                        throw ValidationException::withMessages(['sale' => "Produk {$product->name} sudah tidak aktif. Silakan tinjau ulang transaksi."]);
                    }

                    $configuredPrice = (string) $product->selling_price;
                    if (($data['price_snapshots'][(string) $product->id] ?? null) !== $configuredPrice) {
                        throw ValidationException::withMessages([
                            'sale' => "Harga jual {$product->name} berubah setelah ditinjau. Silakan tinjau ulang transaksi.",
                        ]);
                    }

                    $quantity = BigDecimal::of((string) $item['quantity'])->toScale(3, RoundingMode::Unnecessary);
                    $available = BigDecimal::of((string) $product->current_stock)->toScale(3, RoundingMode::Unnecessary);
                    if (($data['stock_snapshots'][(string) $product->id] ?? null) !== (string) $available) {
                        throw ValidationException::withMessages([
                            'sale' => "Saldo stok {$product->name} berubah setelah ditinjau. Silakan tinjau ulang transaksi.",
                        ]);
                    }

                    $after = $available->minus($quantity);
                    if ($after->isNegative()) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => "Stok {$product->name} tidak cukup untuk penjualan ini.",
                        ]);
                    }

                    $override = $actor->isOwner() ? ($item['selling_price_override'] ?? null) : null;
                    $appliedPrice = $override !== null && $override !== '' ? (string) $override : $configuredPrice;
                    $lineSubtotal = $this->lineSubtotal($appliedPrice, $quantity, $index);
                    $total = $this->addMoney($total, BigInteger::of($lineSubtotal), $index);

                    $saleItem = SaleItem::query()->create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'configured_selling_price' => $configuredPrice,
                        'selling_price' => $appliedPrice,
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
                        'sale_item_id' => $saleItem->id,
                        'movement_type' => 'sale',
                        'quantity' => (string) $quantity->negated()->toScale(3, RoundingMode::Unnecessary),
                        'balance_after' => (string) $after->toScale(3, RoundingMode::Unnecessary),
                        'business_date' => $data['business_date'],
                        'confirmed_at' => $confirmedAt,
                        'created_by' => $actor->id,
                    ]);
                }

                $sale->update(['total_value' => (string) $total]);

                return $sale->load(['customer:id,name', 'creator:id,name', 'items.product:id,name,sku']);
            });
        } catch (QueryException $exception) {
            $duplicate = Sale::query()->where('submission_key', $submissionKey)->first();
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
            throw ValidationException::withMessages(["items.{$index}.quantity" => 'Total penjualan melebihi batas nilai transaksi yang didukung.']);
        }

        return $next;
    }
}
