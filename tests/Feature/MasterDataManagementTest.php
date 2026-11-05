<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductSellingPriceChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_product_and_audits_its_initial_selling_price(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->post(route('products.store'), $this->validProduct());

        $product = Product::query()->where('sku', 'RICE-001')->firstOrFail();

        $response->assertRedirect(route('products.show', $product));
        $this->assertSame('0.000', $product->current_stock);
        $this->assertDatabaseHas('product_selling_price_changes', [
            'product_id' => $product->id,
            'old_price' => null,
            'new_price' => '15000',
            'changed_by' => $owner->id,
        ]);
    }

    public function test_owner_can_update_product_and_audits_selling_price_changes(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->createProduct(['selling_price' => '15000']);

        $response = $this->actingAs($owner)->put(route('products.update', $product), $this->validProduct([
            'sku' => $product->sku,
            'selling_price' => '17500',
        ]));

        $response->assertRedirect(route('products.show', $product));
        $this->assertSame('17500', (string) $product->fresh()->selling_price);
        $this->assertDatabaseHas('product_selling_price_changes', [
            'product_id' => $product->id,
            'old_price' => '15000',
            'new_price' => '17500',
            'changed_by' => $owner->id,
        ]);

        $this->actingAs($owner)->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Riwayat perubahan harga jual')
            ->assertSee('15.000')
            ->assertSee('17.500')
            ->assertSee($owner->name);
    }

    public function test_owner_can_create_supplier_and_customer_records(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('suppliers.store'), [
            'name' => 'Pemasok Beras Jaya',
            'phone' => '081234567890',
            'address' => 'Bandung',
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('customers.store'), [
            'name' => 'Toko Makmur',
            'phone' => '081298765432',
            'address' => 'Jakarta',
        ])->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['name' => 'Pemasok Beras Jaya']);
        $this->assertDatabaseHas('customers', ['name' => 'Toko Makmur']);
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();
        $this->createProduct(['sku' => 'RICE-001']);

        $response = $this->actingAs($owner)->from(route('products.create'))
            ->post(route('products.store'), $this->validProduct(['sku' => 'rice-001']));

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors('sku');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_staff_can_view_selling_price_but_never_receives_purchase_price(): void
    {
        $staff = User::factory()->staff()->create();
        $product = $this->createProduct([
            'purchase_price' => '987654321',
            'selling_price' => '1234567',
        ]);

        $response = $this->actingAs($staff)->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Rp 1.234.567');
        $response->assertDontSee('Rp 987.654.321');
        $this->assertArrayNotHasKey('purchase_price', $product->fresh()->toArray());
    }

    public function test_staff_cannot_manage_products_suppliers_or_customers(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('products.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('suppliers.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('customers.index'))->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function validProduct(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Beras Setra Ramos',
            'sku' => 'RICE-001',
            'description' => 'Beras putih',
            'unit' => 'kg',
            'purchase_price' => '12000',
            'selling_price' => '15000',
            'minimum_stock' => '2.500',
            'is_active' => '1',
        ], $overrides);
    }

    /** @param array<string, mixed> $overrides */
    private function createProduct(array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'name' => 'Beras Setra Ramos',
            'sku' => 'RICE-001',
            'description' => null,
            'unit' => 'kg',
            'purchase_price' => '12000',
            'selling_price' => '15000',
            'minimum_stock' => '2.500',
            'is_active' => true,
        ], $overrides));
    }
}
