<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Supplier;
use App\Models\TransactionCancellation;
use App\Models\User;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WarehouseSeeder::class);
    }

    public function test_owner_cancels_sale_with_audit_record_and_reversal_movement(): void
    {
        $owner = User::factory()->owner()->create();
        [$sale, $product, $movement] = $this->saleWithMovement('8.000', '-2.500');

        $this->actingAs($owner)->post(route('transactions.cancel', ['type' => 'sale', 'transaction' => $sale->id]), [
            'reason' => 'Pesanan dibatalkan pelanggan',
        ])->assertRedirect(route('sales.show', $sale));

        $cancellation = TransactionCancellation::query()->firstOrFail();
        $reversal = InventoryMovement::query()->where('transaction_cancellation_id', $cancellation->id)->firstOrFail();
        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertSame('10.500', (string) $product->fresh()->current_stock);
        $this->assertSame('2.500', (string) $reversal->quantity);
        $this->assertSame($movement->id, $reversal->reverses_movement_id);
        $this->assertSame($owner->id, $cancellation->cancelled_by);
        $this->assertSame('Pesanan dibatalkan pelanggan', $cancellation->reason);
        $this->assertMatchesRegularExpression('/^REV-\d{8}-0001$/', $cancellation->transaction_number);

        $this->post(route('transactions.cancel', ['type' => 'sale', 'transaction' => $sale->id]), [
            'reason' => 'Percobaan pembatalan kedua',
        ])->assertRedirect(route('sales.show', $sale));
        $this->assertDatabaseCount('transaction_cancellations', 1);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_staff_cannot_cancel_and_owner_must_provide_reason(): void
    {
        $staff = User::factory()->staff()->create();
        [$sale] = $this->saleWithMovement('8.000', '-2.500');
        $url = route('transactions.cancel', ['type' => 'sale', 'transaction' => $sale->id]);

        $this->actingAs($staff)->post($url, ['reason' => 'Alasan cukup panjang'])->assertForbidden();
        $this->actingAs(User::factory()->owner()->create())->from(route('sales.show', $sale))
            ->post($url, ['reason' => ''])->assertSessionHasErrors('reason');

        $this->assertSame('confirmed', $sale->fresh()->status);
        $this->assertDatabaseCount('transaction_cancellations', 0);
    }

    public function test_purchase_cancellation_is_rejected_when_reversal_would_make_stock_negative(): void
    {
        $owner = User::factory()->owner()->create();
        [$purchase, $product] = $this->purchaseWithMovement('0.500', '0.500');
        $product->forceFill(['current_stock' => '0.250'])->save();

        $this->actingAs($owner)->from(route('purchases.show', $purchase))
            ->post(route('transactions.cancel', ['type' => 'purchase', 'transaction' => $purchase->id]), [
                'reason' => 'Salah catat penerimaan',
            ])->assertRedirect(route('purchases.show', $purchase))->assertSessionHasErrors('transaction');

        $this->assertSame('confirmed', $purchase->fresh()->status);
        $this->assertSame('0.250', (string) $product->fresh()->current_stock);
        $this->assertDatabaseCount('transaction_cancellations', 0);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_owner_can_cancel_stock_adjustment_and_preserve_original(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->product('10.000');
        $warehouse = \App\Models\Warehouse::query()->where('is_default', true)->firstOrFail();
        $adjustment = StockAdjustment::query()->create([
            'transaction_number' => 'ADJ-20261104-0001',
            'submission_key' => (string) Str::uuid(),
            'warehouse_id' => $warehouse->id,
            'business_date' => '2026-11-04',
            'reason' => 'Hitung awal',
            'created_by' => $owner->id,
            'confirmed_at' => now(),
            'status' => 'confirmed',
        ]);
        $item = StockAdjustmentItem::query()->create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'direction' => 'increase',
            'quantity' => '10.000',
            'unit' => 'kg',
        ]);
        $movement = InventoryMovement::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'stock_adjustment_item_id' => $item->id,
            'movement_type' => 'adjustment',
            'quantity' => '10.000',
            'balance_after' => '10.000',
            'business_date' => '2026-11-04',
            'confirmed_at' => now(),
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)->post(route('transactions.cancel', ['type' => 'adjustment', 'transaction' => $adjustment->id]), [
            'reason' => 'Hitungan awal keliru',
        ])->assertRedirect(route('stock-adjustments.show', $adjustment));

        $this->assertSame('cancelled', $adjustment->fresh()->status);
        $this->assertSame('0.000', (string) $product->fresh()->current_stock);
        $this->assertSame($movement->id, InventoryMovement::query()->whereNotNull('transaction_cancellation_id')->firstOrFail()->reverses_movement_id);
        $this->get(route('stock-adjustments.show', $adjustment))->assertOk()->assertSee('Dibatalkan')->assertSee('Hitungan awal keliru');
    }

    /** @return array{Sale, Product, InventoryMovement} */
    private function saleWithMovement(string $initialStock, string $saleQuantity): array
    {
        $owner = User::factory()->owner()->create();
        $product = $this->product($initialStock);
        $warehouse = \App\Models\Warehouse::query()->where('is_default', true)->firstOrFail();
        $customer = Customer::query()->create(['name' => 'Pelanggan Uji', 'phone' => '081234567890', 'address' => 'Jakarta']);
        $sale = Sale::query()->create([
            'transaction_number' => 'SAL-20261104-0001',
            'submission_key' => (string) Str::uuid(),
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'business_date' => '2026-11-04',
            'total_value' => '37500',
            'created_by' => $owner->id,
            'confirmed_at' => now(),
            'status' => 'confirmed',
        ]);
        $item = SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'configured_selling_price' => '15000',
            'selling_price' => '15000',
            'quantity' => ltrim($saleQuantity, '-'),
            'unit' => 'kg',
            'line_subtotal' => '37500',
        ]);
        $movement = InventoryMovement::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'sale_item_id' => $item->id,
            'movement_type' => 'sale',
            'quantity' => $saleQuantity,
            'balance_after' => '5.500',
            'business_date' => '2026-11-04',
            'confirmed_at' => now(),
            'created_by' => $owner->id,
        ]);

        return [$sale, $product, $movement];
    }

    /** @return array{Purchase, Product} */
    private function purchaseWithMovement(string $quantity, string $stock): array
    {
        $owner = User::factory()->owner()->create();
        $product = $this->product($stock);
        $warehouse = \App\Models\Warehouse::query()->where('is_default', true)->firstOrFail();
        $supplier = Supplier::query()->create(['name' => 'Pemasok Uji', 'phone' => '081234567890', 'address' => 'Jakarta']);
        $purchase = Purchase::query()->create([
            'transaction_number' => 'PUR-20261104-0001',
            'submission_key' => (string) Str::uuid(),
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'business_date' => '2026-11-04',
            'total_value' => '6000',
            'created_by' => $owner->id,
            'confirmed_at' => now(),
            'status' => 'confirmed',
        ]);
        $item = PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'default_purchase_price' => '12000',
            'purchase_price' => '12000',
            'quantity' => $quantity,
            'unit' => 'kg',
            'line_subtotal' => '6000',
        ]);
        InventoryMovement::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'purchase_item_id' => $item->id,
            'movement_type' => 'purchase',
            'quantity' => $quantity,
            'balance_after' => $stock,
            'business_date' => '2026-11-04',
            'confirmed_at' => now(),
            'created_by' => $owner->id,
        ]);

        return [$purchase, $product];
    }

    private function product(string $stock): Product
    {
        $product = Product::query()->create([
            'name' => 'Beras Uji',
            'sku' => 'RICE-TEST-'.Str::upper(Str::random(6)),
            'unit' => 'kg',
            'purchase_price' => '12000',
            'selling_price' => '15000',
            'minimum_stock' => '1.000',
            'is_active' => true,
        ]);
        $product->forceFill(['current_stock' => $stock])->save();

        return $product;
    }
}
