<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::query()->updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Gudang Utama',
                'is_default' => true,
                'is_active' => true,
            ],
        );
    }
}
