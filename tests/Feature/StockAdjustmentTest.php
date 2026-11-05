<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WarehouseSeeder::class);
    }

    public function test_owner_can_record_initial_stock_in_the_ledger_with_reason_and_business_date(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct();
        $this->actingAs($owner)->get(route('stock-adjustments.create'))->assertOk()->assertSee('Alasan penyesuaian');

        $response = $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($product, [
            'quantity' => '125.375',
            'reason' => 'Penghitungan stok awal gudang',
            'business_date' => '2026-11-03',
        ]));

        $adjustment = StockAdjustment::query()->firstOrFail();
        $response->assertRedirect(route('stock-adjustments.show', $adjustment));
        $this->assertSame('125.375', (string) $product->fresh()->current_stock);
        $this->assertSame('2026-11-03', $adjustment->business_date->toDateString());
        $this->assertSame($owner->id, $adjustment->created_by);
        $this->assertMatchesRegularExpression('/^ADJ-\d{8}-0001$/', $adjustment->transaction_number);
        $movement = InventoryMovement::query()->firstOrFail();
        $this->assertSame($product->id, $movement->product_id);
        $this->assertSame('125.375', (string) $movement->quantity);
        $this->assertSame('125.375', (string) $movement->balance_after);
        $this->assertSame('2026-11-03', $movement->business_date->toDateString());
        $this->get(route('stock-adjustments.show', $adjustment))->assertOk()->assertSee($adjustment->transaction_number)->assertSee('Penghitungan stok awal gudang');
        $this->get(route('inventory-movements.index'))->assertOk()->assertSee($adjustment->transaction_number)->assertSee('125,375');
    }

    public function test_adjustment_can_update_multiple_products_atomically(): void
    {
        $owner = User::factory()->owner()->create();
        $first = $this->createProduct(['sku' => 'RICE-001']);
        $second = $this->createProduct(['sku' => 'RICE-002', 'current_stock' => '20.500']);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), [
            'submission_key' => (string) Str::uuid(),
            'business_date' => now(config('app.timezone'))->toDateString(),
            'reason' => 'Koreksi hasil hitung fisik',
            'items' => [
                ['product_id' => $first->id, 'direction' => 'increase', 'quantity' => '1.250'],
                ['product_id' => $second->id, 'direction' => 'decrease', 'quantity' => '0.375'],
            ],
        ])->assertRedirect();

        $this->assertSame('1.250', (string) $first->fresh()->current_stock);
        $this->assertSame('20.125', (string) $second->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertSame('2', (string) StockAdjustment::query()->firstOrFail()->items()->count());
    }

    public function test_adjustment_cannot_make_stock_negative_and_rolls_back_all_rows(): void
    {
        $owner = User::factory()->owner()->create();
        $first = $this->createProduct(['sku' => 'RICE-001']);
        $second = $this->createProduct(['sku' => 'RICE-002', 'current_stock' => '1.000']);

        $response = $this->from(route('stock-adjustments.create'))->actingAs($owner)->post(route('stock-adjustments.store'), [
            'submission_key' => (string) Str::uuid(),
            'business_date' => now(config('app.timezone'))->toDateString(),
            'reason' => 'Koreksi stok fisik',
            'items' => [
                ['product_id' => $first->id, 'direction' => 'increase', 'quantity' => '3.000'],
                ['product_id' => $second->id, 'direction' => 'decrease', 'quantity' => '1.001'],
            ],
        ]);

        $response->assertRedirect(route('stock-adjustments.create'))->assertSessionHasErrors('items.1.quantity');
        $this->assertSame('0.000', (string) $first->fresh()->current_stock);
        $this->assertSame('1.000', (string) $second->fresh()->current_stock);
        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseHas('transaction_number_sequences', [
            'transaction_type' => 'adjustment',
            'sequence_date' => now(config('app.timezone'))->toDateString(),
            'last_number' => 1,
        ]);

        $success = $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($first, ['quantity' => '1.000']));
        $success->assertRedirect();
        $this->assertStringEndsWith('-0002', (string) StockAdjustment::query()->firstOrFail()->transaction_number);
    }

    public function test_duplicate_submission_key_does_not_apply_stock_twice(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct();
        $payload = $this->payload($product, ['quantity' => '2.000']);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $payload)->assertRedirect();
        $this->actingAs($owner)->post(route('stock-adjustments.store'), $payload)->assertRedirect();

        $this->assertSame('2.000', (string) $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_adjustments', 1);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_staff_can_view_history_but_cannot_create_adjustments(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('inventory-movements.index'))->assertOk();
        $this->actingAs($staff)->get(route('stock-adjustments.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('stock-adjustments.store'), [])->assertForbidden();
    }

    public function test_adjustment_requires_a_reason_and_positive_decimal_quantity(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct();

        $response = $this->from(route('stock-adjustments.create'))->actingAs($owner)->post(route('stock-adjustments.store'), [
            'submission_key' => (string) Str::uuid(),
            'business_date' => now(config('app.timezone'))->toDateString(),
            'reason' => '',
            'items' => [['product_id' => $product->id, 'direction' => 'increase', 'quantity' => '0']],
        ]);

        $response->assertSessionHasErrors(['reason', 'items.0.quantity']);
        $this->assertSame('0.000', (string) $product->fresh()->current_stock);
    }

    public function test_transaction_number_expands_past_four_digits(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct();
        \App\Models\TransactionNumberSequence::query()->create([
            'transaction_type' => 'adjustment',
            'sequence_date' => now(config('app.timezone'))->toDateString(),
            'last_number' => 9999,
        ]);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($product))->assertRedirect();

        $this->assertStringEndsWith('-10000', (string) StockAdjustment::query()->firstOrFail()->transaction_number);
    }

    /** @param array<string, mixed> $overrides */
    private function createProduct(array $overrides = []): Product
    {
        $stock = $overrides['current_stock'] ?? '0.000';
        unset($overrides['current_stock']);

        $product = Product::query()->create(array_merge([
            'name' => 'Beras Setra Ramos',
            'sku' => 'RICE-001',
            'unit' => 'kg',
            'purchase_price' => '12000',
            'selling_price' => '15000',
            'minimum_stock' => '2.500',
            'is_active' => true,
        ], $overrides));

        if ($stock !== '0.000') {
            $product->forceFill(['current_stock' => $stock])->save();
        }

        return $product;
    }

    /** @return array<string, mixed> */
    private function payload(Product $product, array $overrides = []): array
    {
        $payload = [
            'submission_key' => (string) Str::uuid(),
            'business_date' => now(config('app.timezone'))->toDateString(),
            'reason' => 'Penambahan stok awal',
            'items' => [['product_id' => $product->id, 'direction' => 'increase', 'quantity' => '10.000']],
        ];

        if (array_key_exists('quantity', $overrides)) {
            $payload['items'][0]['quantity'] = $overrides['quantity'];
            unset($overrides['quantity']);
        }

        return array_merge($payload, $overrides);
    }
}
