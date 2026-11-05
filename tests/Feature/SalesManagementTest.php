<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SaleService;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WarehouseSeeder::class);
    }

    public function test_owner_override_is_snapshotted_and_line_rounds_half_up(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct(['selling_price' => '1001'], '2.500');
        $this->customer();
        $draftKey = (string) Str::uuid();

        $this->actingAs($owner)->get(route('sales.create'))
            ->assertOk()
            ->assertSee('Override harga jual')
            ->assertSee('Rp 1.001');

        $this->prepare($owner, $draftKey, $product, [
            'quantity' => '0.500',
            'selling_price_override' => '1003',
            'override_reason' => 'Harga sesuai kesepakatan pelanggan',
        ]);

        $this->get(route('sales.review', $draftKey))
            ->assertOk()
            ->assertSee('Rp 502')
            ->assertSee('Harga sesuai kesepakatan pelanggan');

        $this->post(route('sales.confirm', $draftKey))->assertRedirect();

        $sale = Sale::query()->firstOrFail();
        $item = SaleItem::query()->firstOrFail();
        $this->assertMatchesRegularExpression('/^SAL-\d{8}-0001$/', $sale->transaction_number);
        $this->assertSame('1001', (string) $item->configured_selling_price);
        $this->assertSame('1003', (string) $item->selling_price);
        $this->assertSame('Harga sesuai kesepakatan pelanggan', $item->override_reason);
        $this->assertSame('502', (string) $item->line_subtotal);
        $this->assertSame('2.000', (string) $product->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_movements', [
            'sale_item_id' => $item->id,
            'movement_type' => 'sale',
            'quantity' => '-0.500',
            'balance_after' => '2.000',
        ]);
    }

    public function test_staff_can_sell_at_configured_price_and_see_transaction_total(): void
    {
        $staff = User::factory()->staff()->create();
        $product = $this->createProduct(['selling_price' => '15000', 'purchase_price' => '987654321'], '5.000');
        $this->customer();
        $draftKey = (string) Str::uuid();
        $this->prepare($staff, $draftKey, $product, ['quantity' => '1.250']);

        $this->get(route('sales.review', $draftKey))
            ->assertOk()
            ->assertSee('Rp 15.000')
            ->assertSee('Rp 18.750')
            ->assertSee('Total penjualan')
            ->assertDontSee('Rp 987.654.321')
            ->assertDontSee('Override harga jual');

        $this->post(route('sales.confirm', $draftKey))->assertRedirect();
        $sale = Sale::query()->with('items')->firstOrFail();
        $this->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('Rp 18.750')
            ->assertDontSee('Rp 987.654.321')
            ->assertDontSee('Alasan override');

        $this->assertSame('3.750', (string) $product->fresh()->current_stock);
        $this->assertSame('18750', (string) $sale->total_value);
        $this->assertSame('15000', (string) $sale->items->first()->selling_price);
        $this->assertArrayNotHasKey('override_reason', $sale->items->first()->toArray());
        $this->assertArrayNotHasKey('purchase_price', $product->fresh()->toArray());
    }

    public function test_owner_override_requires_reason_and_staff_cannot_submit_price_override(): void
    {
        $product = $this->createProduct([], '4.000');
        $this->customer();
        $owner = User::factory()->owner()->create();
        $key = (string) Str::uuid();
        $this->from(route('sales.create'))->actingAs($owner)->post(route('sales.prepare'), $this->payload($key, $product, [
            'selling_price_override' => '14000',
        ]))->assertSessionHasErrors('items.0.override_reason');

        $staff = User::factory()->staff()->create();
        $staffKey = (string) Str::uuid();
        $this->from(route('sales.create'))->actingAs($staff)->post(route('sales.prepare'), $this->payload($staffKey, $product, [
            'selling_price_override' => '1',
            'override_reason' => 'Percobaan akses',
        ]))->assertSessionHasErrors('items.0.selling_price_override');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame('4.000', (string) $product->fresh()->current_stock);
    }

    public function test_insufficient_stock_cannot_be_confirmed_even_if_request_is_forged(): void
    {
        $staff = User::factory()->staff()->create();
        $product = $this->createProduct([], '1.000');
        $this->customer();
        $draftKey = (string) Str::uuid();
        $this->prepare($staff, $draftKey, $product, ['quantity' => '1.001']);

        $this->get(route('sales.review', $draftKey))
            ->assertOk()
            ->assertSee('Stok tidak mencukupi')
            ->assertSee('Stok tidak mencukupi');

        $this->post(route('sales.confirm', $draftKey))
            ->assertRedirect(route('sales.review', $draftKey))
            ->assertSessionHasErrors('items.0.quantity');
        $this->assertSame('1.000', (string) $product->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_changed_price_after_review_requires_a_new_review(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct([], '3.000');
        $this->customer();
        $draftKey = (string) Str::uuid();
        $this->prepare($owner, $draftKey, $product);
        $this->get(route('sales.review', $draftKey))->assertOk();

        $product->forceFill(['selling_price' => '16000'])->save();

        $this->post(route('sales.confirm', $draftKey))
            ->assertRedirect(route('sales.review', $draftKey))
            ->assertSessionHasErrors('sale');
        $this->assertSame('3.000', (string) $product->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_repeated_confirmation_key_does_not_reduce_stock_twice(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct([], '5.000');
        $this->customer();
        $draftKey = (string) Str::uuid();
        $this->prepare($owner, $draftKey, $product, ['quantity' => '2.250']);
        $this->get(route('sales.review', $draftKey))->assertOk();
        $draftData = session("sale_drafts.{$draftKey}.data");
        $service = app(SaleService::class);

        $first = $service->confirm($draftData, $draftKey, $owner);
        $second = $service->confirm($draftData, $draftKey, $owner);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('2.750', (string) $product->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_sale_notes_are_saved(): void
    {
        $staff = User::factory()->staff()->create();
        $product = $this->createProduct([], '3.000');
        $this->customer();
        $draftKey = (string) Str::uuid();
        $this->prepare($staff, $draftKey, $product, ['notes' => 'Diantar sore hari']);
        $this->get(route('sales.review', $draftKey))->assertOk()->assertSee('Diantar sore hari');
        $this->post(route('sales.confirm', $draftKey))->assertRedirect();
        $sale = Sale::query()->firstOrFail();

        $this->assertSame('Diantar sore hari', $sale->notes);
        $this->get(route('sales.show', $sale))->assertSee('Diantar sore hari');
    }

    /** @param array<string, mixed> $overrides */
    private function prepare(User $user, string $draftKey, Product $product, array $overrides = []): void
    {
        $this->actingAs($user)->post(route('sales.prepare'), $this->payload($draftKey, $product, $overrides))
            ->assertRedirect(route('sales.review', $draftKey));
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function payload(string $draftKey, Product $product, array $overrides = []): array
    {
        $itemOverrides = [];
        foreach (['quantity', 'selling_price_override', 'override_reason'] as $key) {
            if (array_key_exists($key, $overrides)) {
                $itemOverrides[$key] = $overrides[$key];
                unset($overrides[$key]);
            }
        }

        return array_merge([
            'draft_key' => $draftKey,
            'customer_id' => $this->customer()->id,
            'business_date' => now(config('app.timezone'))->toDateString(),
            'items' => [array_merge(['product_id' => $product->id, 'quantity' => '1.000'], $itemOverrides)],
        ], $overrides);
    }

    private function customer(): Customer
    {
        return Customer::query()->firstOrCreate(['name' => 'Toko Makmur'], [
            'phone' => '081234567890',
            'address' => 'Jakarta',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function createProduct(array $overrides = [], string $stock = '0.000'): Product
    {
        $product = Product::query()->create(array_merge([
            'name' => 'Beras Setra Ramos',
            'sku' => 'RICE-001',
            'unit' => 'kg',
            'purchase_price' => '12000',
            'selling_price' => '15000',
            'minimum_stock' => '2.500',
            'is_active' => true,
        ], $overrides));

        $product->forceFill(['current_stock' => $stock])->save();

        return $product;
    }
}
