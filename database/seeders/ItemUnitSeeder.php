<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Database\Seeder;

class ItemUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create units for alat items (items with units like 'unit', 'set')
        $alatItems = Item::whereHas('category', function ($q) {
            $q->where('type', 'alat');
        })->get();

        foreach ($alatItems as $item) {
            // Create 2-5 units per alat item
            $unitCount = rand(2, 5);

            for ($i = 1; $i <= $unitCount; $i++) {
                $condition = $i === 1 ? 'baik' : fake()->randomElement(['baik', 'rusak_ringan', 'rusak_berat']);

                $unitData = [
                    'item_id' => $item->id,
                    'serial_number' => $item->code.'-'.str_pad($i, 3, '0', STR_PAD_LEFT),
                    'asset_tag' => 'AT-'.strtoupper($item->code).'-'.str_pad($i, 3, '0', STR_PAD_LEFT),
                    'condition' => $condition,
                    'location' => $item->location,
                    'purchase_date' => fake()->dateTimeBetween('-3 years', '-6 months'),
                    'last_calibration_date' => fake()->dateTimeBetween('-1 year', 'now'),
                    'next_calibration_date' => fake()->dateTimeBetween('now', '+1 year'),
                    'expiry_date' => $item->isAlat() ? null : fake()->optional(0.1)->dateTimeBetween('now', '+2 years'),
                    'notes' => fake()->optional(0.3)->sentence(),
                    'created_by' => 1, // admin
                ];

                ItemUnit::create($unitData);
            }
        }

        // For bahan items, we don't create units (they're tracked by stock quantity)
        // But we can create some units for special tracking if needed
        $this->command->info('Created '.ItemUnit::count().' item units');
    }
}
