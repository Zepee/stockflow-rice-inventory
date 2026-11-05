<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\WarehouseSeeder;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseManagementTest extends TestCase
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
        $product = $this->createProduct(['purchase_price' => '1001']);
        $this->supplier();
        $draftKey = (string) Str::uuid();
        $this->actingAs($owner)->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Override harga beli')
            ->assertSee('Rp 1.001');
        $this->prepare($owner, $draftKey, $product, [
            'quantity' => '0.500',
            'purchase_price_override' => '1003',
            'override_reason' => 'Harga sesuai nota pemasok',
            'notes' => 'Penerimaan sesuai nota 001',
        ]);

        $this->get(route('purchases.review', $draftKey))
            ->assertOk()
            ->assertSee('Rp 502')
            ->assertSee('Harga sesuai nota pemasok')
            ->assertSee('Penerimaan sesuai nota 001');

        $this->post(route('purchases.confirm', $draftKey))->assertRedirect();

        $purchase = Purchase::query()->firstOrFail();
        $item = PurchaseItem::query()->firstOrFail();
        $this->assertMatchesRegularExpression('/^PUR-\d{8}-0001$/', $purchase->transaction_number);
        $this->assertSame('502', (string) $item->line_subtotal);
        $this->assertSame('1001', (string) $item->default_purchase_price);
        $this->assertSame('1003', (string) $item->purchase_price);
        $this->assertSame('Harga sesuai nota pemasok', $item->override_reason);
        $this->assertSame('Penerimaan sesuai nota 001', $purchase->notes);
        $this->assertSame('0.500', (string) $product->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_movements', [
            'purchase_item_id' => $item->id,
            'movement_type' => 'purchase',
            'quantity' => '0.500',
            'balance_after' => '0.500',
        ]);
    }

    public function test_staff_can_complete_purchase_without_seeing_purchase_prices(): void
    {
        $staff = User::factory()->staff()->create();
        $product = $this->createProduct(['purchase_price' => '987654321']);
        $this->supplier();
        $this->actingAs($staff)->get(route('purchases.create'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee('Rp 987.654.321')
            ->assertDontSee('purchase_price');

        $draftKey = (string) Str::uuid();
        $this->prepare($staff, $draftKey, $product, ['quantity' => '1.250']);
        $this->get(route('purchases.review', $draftKey))
            ->assertOk()
            ->assertDontSee('Rp 987.654.321')
            ->assertDontSee('Total pembelian')
            ->assertDontSee('purchase_price');

        $this->post(route('purchases.confirm', $draftKey))->assertRedirect();
        $purchase = Purchase::query()->with('items')->firstOrFail();
        $this->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertDontSee('Rp 987.654.321')
            ->assertDontSee('Total pembelian')
            ->assertDontSee('Harga beli aktual');

        $this->assertSame('1234567901', (string) $purchase->items->first()->line_subtotal);
        $this->assertArrayNotHasKey('total_value', $purchase->toArray());
        $this->assertArrayNotHasKey('purchase_price', $purchase->items->first()->toArray());
        $this->assertArrayNotHasKey('purchase_price', $product->fresh()->toArray());
    }

    public function test_override_requires_a_reason_and_staff_cannot_submit_an_override(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct();
        $draftKey = (string) Str::uuid();

        $this->from(route('purchases.create'))->actingAs($owner)->post(route('purchases.prepare'), $this->payload($draftKey, $product, [
            'purchase_price_override' => '13000',
        ]))->assertRedirect(route('purchases.create'))->assertSessionHasErrors('items.0.override_reason');

        $staff = User::factory()->staff()->create();
        $staffDraftKey = (string) Str::uuid();
        $this->from(route('purchases.create'))->actingAs($staff)->post(route('purchases.prepare'), $this->payload($staffDraftKey, $product, [
            'purchase_price_override' => '1',
            'override_reason' => 'Percobaan akses',
        ]))->assertSessionHasErrors('items.0.purchase_price_override');

        $this->assertDatabaseCount('purchases', 0);
        $this->assertSame('0.000', (string) $product->fresh()->current_stock);
    }

    public function test_changed_default_price_requires_owner_or_staff_to_review_again(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct();
        $draftKey = (string) Str::uuid();
        $this->prepare($owner, $draftKey, $product);
        $this->get(route('purchases.review', $draftKey))->assertOk();

        $product->forceFill(['purchase_price' => '13000'])->save();

        $this->post(route('purchases.confirm', $draftKey))
            ->assertRedirect(route('purchases.review', $draftKey))
            ->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('purchases', 0);
        $this->assertSame('0.000', (string) $product->fresh()->current_stock);
    }

    public function test_staff_purchase_numbers_and_financial_values_stay_out_of_history_responses(): void
    {
        $staff = User::factory()->staff()->create();
        $product = $this->createProduct();
        $draftKey = (string) Str::uuid();
        $this->prepare($staff, $draftKey, $product);
        $this->get(route('purchases.review', $draftKey))->assertOk();
        $this->post(route('purchases.confirm', $draftKey))->assertRedirect();

        $this->actingAs($staff)->get(route('purchases.index'))->assertOk()->assertDontSee('Rp');
        $this->get(route('inventory-movements.index'))->assertOk()->assertSee('PUR-');
    }

    public function test_repeated_confirmation_with_the_same_draft_key_does_not_add_stock_twice(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct();
        $draftKey = (string) Str::uuid();
        $this->prepare($owner, $draftKey, $product, ['quantity' => '2.250']);
        $this->get(route('purchases.review', $draftKey))->assertOk();
        $draftData = session("purchase_drafts.{$draftKey}.data");
        $service = app(PurchaseService::class);

        $first = $service->confirm($draftData, $draftKey, $owner);
        $second = $service->confirm($draftData, $draftKey, $owner);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('2.250', (string) $product->fresh()->current_stock);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('purchase_items', 1);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_base_unit_cannot_change_after_stock_has_moved(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct();
        $draftKey = (string) Str::uuid();
        $this->prepare($owner, $draftKey, $product);
        $this->get(route('purchases.review', $draftKey))->assertOk();
        $this->post(route('purchases.confirm', $draftKey))->assertRedirect();

        $this->from(route('products.edit', $product))->actingAs($owner)->put(route('products.update', $product), [
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => '',
            'unit' => 'ton',
            'purchase_price' => '12000',
            'selling_price' => '15000',
            'minimum_stock' => '2.500',
            'is_active' => '1',
        ])->assertSessionHasErrors('unit');

        $this->assertSame('kg', $product->fresh()->unit);
    }

    /** @param array<string, mixed> $overrides */
    private function prepare(User $user, string $draftKey, Product $product, array $overrides = []): void
    {
        $this->actingAs($user)->post(route('purchases.prepare'), $this->payload($draftKey, $product, $overrides))
            ->assertRedirect(route('purchases.review', $draftKey));
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function payload(string $draftKey, Product $product, array $overrides = []): array
    {
        $notes = $overrides['notes'] ?? null;
        unset($overrides['notes']);

        return array_merge([
            'draft_key' => $draftKey,
            'supplier_id' => $this->supplier()->id,
            'business_date' => now(config('app.timezone'))->toDateString(),
            'items' => [array_merge([
                'product_id' => $product->id,
                'quantity' => '1.000',
            ], $overrides)],
        ], $notes === null ? [] : ['notes' => $notes]);
    }

    private function supplier(): Supplier
    {
        return Supplier::query()->firstOrCreate([
            'name' => 'Pemasok Beras Jaya',
        ], [
            'phone' => '081234567890',
            'address' => 'Bandung',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function createProduct(array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'name' => 'Beras Setra Ramos',
            'sku' => 'RICE-001',
            'unit' => 'kg',
            'purchase_price' => '12000',
            'selling_price' => '15000',
            'minimum_stock' => '2.500',
            'is_active' => true,
        ], $overrides));
    }
}
