<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockAdjustmentService;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WarehouseSeeder::class);
    }

    public function test_dashboard_uses_business_date_excludes_cancelled_values_and_hides_finance_from_staff(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->staff()->create();
        $product = $this->product('DEMO-RICE-001', '20.000');
        $today = now('Asia/Jakarta')->toDateString();

        app(StockAdjustmentService::class)->confirm([
            'submission_key' => (string) Str::uuid(),
            'business_date' => $today,
            'reason' => '[DEMO] Saldo awal dashboard test',
            'items' => [['product_id' => $product->id, 'direction' => 'increase', 'quantity' => '12.000']],
        ], $owner);

        $supplier = Supplier::query()->create(['name' => '[DEMO] Pemasok Test', 'phone' => '0000000000', 'address' => 'Alamat demo']);
        $customer = Customer::query()->create(['name' => '[DEMO] Pelanggan Test', 'phone' => '0000000000', 'address' => 'Alamat demo']);
        $this->purchase($owner, $supplier, 'PUR-DEMO-ACTIVE', '300000', 'confirmed', $today);
        $this->purchase($owner, $supplier, 'PUR-DEMO-CANCELLED', '700000', 'cancelled', $today);
        $this->sale($owner, $customer, 'SAL-DEMO-ACTIVE', '450000', 'confirmed', $today);
        $this->sale($owner, $customer, 'SAL-DEMO-CANCELLED', '800000', 'cancelled', $today);
        $this->assertSame('300000', (string) Purchase::query()->whereDate('business_date', $today)->where('status', 'confirmed')->sum('total_value'));
        $this->assertSame('450000', (string) Sale::query()->whereDate('business_date', $today)->where('status', 'confirmed')->sum('total_value'));

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('12 kg')
            ->assertSee('Stok masuk hari ini')
            ->assertSee('Nilai pembelian hari ini')
            ->assertSee('Nilai penjualan hari ini')
            ->assertSee('Rp 300.000')
            ->assertSee('Rp 450.000')
            ->assertDontSee('Rp 700.000')
            ->assertDontSee('Rp 800.000');

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('12 kg')
            ->assertDontSee('Nilai pembelian hari ini')
            ->assertDontSee('Nilai penjualan hari ini')
            ->assertDontSee('Rp 300.000');
    }

    public function test_owner_sales_report_keeps_cancelled_rows_but_active_summary_excludes_them(): void
    {
        $owner = User::factory()->owner()->create();
        $customer = Customer::query()->create(['name' => '[DEMO] Pelanggan Laporan', 'phone' => '0000000000', 'address' => 'Alamat demo']);
        $today = now('Asia/Jakarta')->toDateString();
        $this->sale($owner, $customer, 'SAL-REPORT-ACTIVE', '450000', 'confirmed', $today);
        $this->sale($owner, $customer, 'SAL-REPORT-CANCELLED', '800000', 'cancelled', $today);

        $this->actingAs($owner)->get(route('reports.index', ['type' => 'sales', 'from' => $today, 'to' => $today]))
            ->assertOk()
            ->assertSee('Nilai transaksi aktif:')
            ->assertSee('Rp 450.000')
            ->assertDontSee('Rp 1.250.000')
            ->assertSee('SAL-REPORT-ACTIVE')
            ->assertSee('SAL-REPORT-CANCELLED')
            ->assertSee('Dibatalkan');
    }

    public function test_staff_can_export_operational_reports_but_cannot_access_financial_reports(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('reports.index', ['type' => 'sales']))->assertForbidden();
        $this->actingAs($staff)->get(route('reports.export', ['type' => 'purchases']))->assertForbidden();

        $this->actingAs($staff)->get(route('reports.index', ['type' => 'inventory']))->assertOk();
        $this->actingAs($staff)->get(route('reports.export', ['type' => 'inventory']))->assertDownload();
    }

    public function test_movement_report_filters_by_product_and_type_and_csv_omits_purchase_price(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->staff()->create();
        $first = $this->product('DEMO-RICE-101', '20.000');
        $second = $this->product('DEMO-RICE-102', '12000000.000');
        $today = now('Asia/Jakarta')->toDateString();

        app(StockAdjustmentService::class)->confirm([
            'submission_key' => (string) Str::uuid(),
            'business_date' => $today,
            'reason' => '[DEMO] Filter movement report',
            'items' => [
                ['product_id' => $first->id, 'direction' => 'increase', 'quantity' => '1.250'],
                ['product_id' => $second->id, 'direction' => 'increase', 'quantity' => '2.000'],
            ],
        ], $owner);

        $query = ['type' => 'movements', 'from' => $today, 'to' => $today, 'product_id' => $first->id, 'movement_type' => 'adjustment'];
        $report = $this->actingAs($staff)->get(route('reports.index', $query));
        $report
            ->assertOk()
            ->assertSee('DEMO-RICE-101')
            ->assertDontSeeHtml('DEMO-RICE-102</td>');

        $export = $this->actingAs($staff)->get(route('reports.export', array_merge(['type' => 'movements'], array_diff_key($query, ['type' => true]))));
        $export->assertDownload();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('DEMO-RICE-101', $csv);
        $this->assertStringNotContainsString('DEMO-RICE-102', $csv);
        $this->assertStringNotContainsString('12000000', $csv);
    }

    private function product(string $sku, string $minimumStock): Product
    {
        return Product::query()->create([
            'name' => '[DEMO] '.$sku,
            'sku' => $sku,
            'unit' => 'kg',
            'purchase_price' => '12000000',
            'selling_price' => '15000',
            'minimum_stock' => $minimumStock,
            'is_active' => true,
        ]);
    }

    private function purchase(User $owner, Supplier $supplier, string $number, string $value, string $status, string $date): void
    {
        \App\Models\Warehouse::query()->where('is_default', true)->firstOrFail();
        Purchase::query()->create([
            'transaction_number' => $number,
            'submission_key' => (string) Str::uuid(),
            'warehouse_id' => \App\Models\Warehouse::query()->where('is_default', true)->value('id'),
            'supplier_id' => $supplier->id,
            'business_date' => $date,
            'total_value' => $value,
            'created_by' => $owner->id,
            'confirmed_at' => now('Asia/Jakarta'),
            'status' => $status,
        ]);
    }

    private function sale(User $owner, Customer $customer, string $number, string $value, string $status, string $date): void
    {
        Sale::query()->create([
            'transaction_number' => $number,
            'submission_key' => (string) Str::uuid(),
            'warehouse_id' => \App\Models\Warehouse::query()->where('is_default', true)->value('id'),
            'customer_id' => $customer->id,
            'business_date' => $date,
            'total_value' => $value,
            'created_by' => $owner->id,
            'confirmed_at' => now('Asia/Jakarta'),
            'status' => $status,
        ]);
    }
}
