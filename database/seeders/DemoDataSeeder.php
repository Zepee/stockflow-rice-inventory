<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\StockAdjustmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('role', 'owner')->orderBy('id')->first();
        if (! $owner) {
            $this->command?->error('Buat akun Owner terlebih dahulu sebelum menambahkan data DEMO.');

            return;
        }

        $supplier = Supplier::query()->firstOrCreate(['name' => 'Mitra Padi Sejahtera'], [
            'phone' => '0000000000',
            'address' => 'Alamat belum dicatat.',
        ]);
        $customer = Customer::query()->firstOrCreate(['name' => 'Toko Beras Nusantara'], [
            'phone' => '0000000000',
            'address' => 'Alamat belum dicatat.',
        ]);

        // Product identities are real retail products. Prices below are practice
        // values, not market quotes. Existing prices and balances are preserved.
        $definitions = [
            [
                'legacy_sku' => 'DEMO-RICE-001', 'sku' => 'SANIA-PREMIUM-IR64', 'name' => 'Beras Premium Sania',
                'description' => 'Beras Premium Sania berbahan varietas IR-64. Nama dan varietas produk mengacu pada katalog merek Sania.',
                'purchase_price' => '12500', 'selling_price' => '16000', 'minimum_stock' => '20.000',
            ],
            [
                'legacy_sku' => 'DEMO-RICE-002', 'sku' => 'TOPIKOKI-SETRA-RAMOS', 'name' => 'Topi Koki Setra Ramos',
                'description' => 'Beras Topi Koki Setra Ramos, produk beras bertekstur pulen yang dijual dalam beberapa ukuran kemasan.',
                'purchase_price' => '14000', 'selling_price' => '17500', 'minimum_stock' => '15.000',
            ],
            [
                'legacy_sku' => 'DEMO-RICE-003', 'sku' => 'TOPIKOKI-LONG-GRAIN-CRYSTAL', 'name' => 'Topi Koki Long Grain Crystal',
                'description' => 'Beras Topi Koki Long Grain Crystal, produk beras berbutir panjang dari katalog resmi Topi Koki.',
                'purchase_price' => '11000', 'selling_price' => '14000', 'minimum_stock' => '10.000',
            ],
            [
                'sku' => 'TOPIKOKI-PANDAN-WANGI-CIANJUR', 'name' => 'Topi Koki Pandan Wangi Cianjur',
                'description' => 'Beras aromatik berbutir pendek dengan aroma pandan, tercantum dalam katalog resmi Topi Koki.',
                'purchase_price' => '16000', 'selling_price' => '20000', 'minimum_stock' => '10.000',
            ],
            [
                'sku' => 'TOPIKOKI-SHORT-GRAIN-HERITAGE', 'name' => 'Topi Koki Short Grain Heritage',
                'description' => 'Beras berbutir pendek dengan tekstur pulen dan lekat, tercantum dalam katalog resmi Topi Koki.',
                'purchase_price' => '20000', 'selling_price' => '25000', 'minimum_stock' => '10.000',
            ],
            [
                'sku' => 'TOPIKOKI-SETRA-ROYALE', 'name' => 'Topi Koki Setra Royale',
                'description' => 'Beras berbutir panjang dari lini Topi Koki Setra Royale.',
                'purchase_price' => '17000', 'selling_price' => '21000', 'minimum_stock' => '10.000',
            ],
            [
                'sku' => 'TOPIKOKI-SUPER-SLYP', 'name' => 'Topi Koki Super Slyp',
                'description' => 'Beras berbutir panjang dengan tingkat kepulenan sedang dari katalog Topi Koki.',
                'purchase_price' => '13500', 'selling_price' => '17000', 'minimum_stock' => '10.000',
            ],
            [
                'sku' => 'SUPER-BELIDA-IR42', 'name' => 'Super Belida IR42',
                'description' => 'Beras pendek tipe IR42 dengan nasi pera, dipasarkan sebagai Super Belida.',
                'purchase_price' => '14000', 'selling_price' => '17500', 'minimum_stock' => '10.000',
            ],
            [
                'sku' => 'HOKAIRI-JAPONICA', 'name' => 'Hokairi Japonica',
                'description' => 'Beras varietas Japonica untuk hidangan Jepang, tercantum dalam katalog Topi Koki.',
                'purchase_price' => '25000', 'selling_price' => '32000', 'minimum_stock' => '5.000',
            ],
        ];

        $products = collect($definitions)->map(function (array $data) use ($owner): Product {
            $product = Product::query()->where('sku', $data['sku'])->first()
                ?? (isset($data['legacy_sku']) ? Product::query()->where('sku', $data['legacy_sku'])->first() : null);

            if ($product) {
                $product->fill([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'description' => $data['description'],
                ])->save();

                return $product;
            }

            return app(ProductService::class)->create([
                'sku' => $data['sku'],
                'name' => $data['name'],
                'description' => $data['description'],
                'purchase_price' => $data['purchase_price'],
                'selling_price' => $data['selling_price'],
                'minimum_stock' => $data['minimum_stock'],
                'unit' => 'kg',
                'is_active' => true,
            ], $owner);
        });
        $businessDate = now('Asia/Jakarta')->toDateString();

        $adjustmentReason = 'Pencatatan saldo awal persediaan gudang.';
        if (! StockAdjustment::query()->where('reason', $adjustmentReason)->exists()) {
            app(StockAdjustmentService::class)->confirm([
                'submission_key' => (string) Str::uuid(),
                'business_date' => $businessDate,
                'reason' => $adjustmentReason,
                'items' => [
                    ['product_id' => $products[0]->id, 'direction' => 'increase', 'quantity' => '120.000'],
                    ['product_id' => $products[1]->id, 'direction' => 'increase', 'quantity' => '65.000'],
                    ['product_id' => $products[2]->id, 'direction' => 'increase', 'quantity' => '8.000'],
                ],
            ], $owner);
        }

        $purchaseNote = 'Penerimaan stok beras untuk pengisian persediaan gudang.';
        if (! Purchase::query()->where('notes', $purchaseNote)->exists()) {
            $purchaseData = [
                'supplier_id' => $supplier->id,
                'business_date' => $businessDate,
                'notes' => $purchaseNote,
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => '25.000'],
                    ['product_id' => $products[2]->id, 'quantity' => '4.000'],
                ],
            ];
            $preview = app(PurchaseService::class)->preview($purchaseData, $owner);
            $purchaseData['price_snapshots'] = $preview['price_snapshots'];
            app(PurchaseService::class)->confirm($purchaseData, (string) Str::uuid(), $owner);
        }

        $saleNote = 'Penjualan beras kepada pelanggan.';
        if (! Sale::query()->where('notes', $saleNote)->exists()) {
            $saleData = [
                'customer_id' => $customer->id,
                'business_date' => $businessDate,
                'notes' => $saleNote,
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => '15.500'],
                    ['product_id' => $products[1]->id, 'quantity' => '5.250'],
                    ['product_id' => $products[2]->id, 'quantity' => '5.000'],
                ],
            ];
            $preview = app(SaleService::class)->preview($saleData, $owner);
            $saleData['price_snapshots'] = $preview['price_snapshots'];
            $saleData['stock_snapshots'] = $preview['stock_snapshots'];
            app(SaleService::class)->confirm($saleData, (string) Str::uuid(), $owner);
        }

        $this->command?->info('Katalog latihan tersedia: 9 produk beras, 1 pemasok, 1 pelanggan, 1 penyesuaian, 1 pembelian, dan 1 penjualan. SKU produk tambahan memakai stok awal 0; harga awal merupakan nilai latihan.');
    }
}
