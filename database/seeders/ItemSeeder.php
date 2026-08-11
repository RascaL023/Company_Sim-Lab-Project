<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Dataset kecil & mudah dipahami:
     * - 2 alat (tanpa stock, tanpa location katalog; unit fisik di item_units)
     * - 3 bahan (dengan stock_quantity, minimum_stock, dan location_id)
     */
    public function run(): void
    {
        $createdBy = User::where('role', 'admin_sistem')->value('id') ?? User::first()->id;

        $mikroskop = Category::where('name', 'Mikroskop')->value('id');
        $sentrifus = Category::where('name', 'Sentrifus')->value('id');
        $reagen = Category::where('name', 'Reagen Kimia')->value('id');
        $konsumabel = Category::where('name', 'Konsumabel Lab')->value('id');
        $gudangKimia = Location::where('code', 'LOC-GUDANG-KIMIA')->value('id');
        $gudangKonsumabel = Location::where('code', 'LOC-GUDANG-KONSUMABEL')->value('id');

        $items = [
            // Alat: stock & lokasi katalog = NULL (jumlah dari ItemUnit)
            ['category_id' => $mikroskop, 'code' => 'MCS-001', 'name' => 'Mikroskop Binocular Olympus CX23', 'unit' => 'unit'],
            ['category_id' => $sentrifus, 'code' => 'SNF-001', 'name' => 'Sentrifus Mikro Eppendorf 5424R', 'unit' => 'unit'],
            // Bahan: stock/lokasi di katalog
            ['category_id' => $reagen, 'code' => 'HCL-001', 'name' => 'Asam Klorida 37%', 'unit' => 'liter', 'stock_quantity' => 120, 'minimum_stock' => 20, 'location_id' => $gudangKimia],
            ['category_id' => $reagen, 'code' => 'ETN-001', 'name' => 'Ethanol 96%', 'unit' => 'liter', 'stock_quantity' => 50, 'minimum_stock' => 10, 'location_id' => $gudangKimia],
            ['category_id' => $konsumabel, 'code' => 'TIP-001', 'name' => 'Tip Pipet Steril 0,1-10µl', 'unit' => 'box', 'stock_quantity' => 40, 'minimum_stock' => 10, 'location_id' => $gudangKonsumabel],
        ];

        foreach ($items as $item) {
            Item::create(array_merge($item, ['manufacturer' => null, 'description' => null, 'created_by' => $createdBy]));
        }

        $this->command->info('Created '.Item::count().' items');
    }
}
