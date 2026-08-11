<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class ItemUnitSeeder extends Seeder
{
    /**
     * Beberapa unit fisik untuk setiap alat, masing-masing dengan lokasi.
     * Bahan tidak memiliki unit (dilacak lewat stock_quantity).
     */
    public function run(): void
    {
        $createdBy = User::where('role', 'admin_sistem')->value('id') ?? User::first()->id;
        $labMikroskopi = Location::where('code', 'LOC-MIKROSKOPI')->value('id');
        $labSentrifugasi = Location::where('code', 'LOC-SENTRIFUGASI')->value('id');

        $mikroskop = Item::where('code', 'MCS-001')->first();
        $sentrifus = Item::where('code', 'SNF-001')->first();

        $units = [
            ['item_id' => $mikroskop->id, 'serial_number' => 'CX23-001', 'asset_tag' => 'AT-MCS-001', 'condition' => 'baik', 'location_id' => $labMikroskopi],
            ['item_id' => $mikroskop->id, 'serial_number' => 'CX23-002', 'asset_tag' => 'AT-MCS-002', 'condition' => 'baik', 'location_id' => $labMikroskopi],
            ['item_id' => $sentrifus->id, 'serial_number' => 'SNF-001', 'asset_tag' => 'AT-SNF-001', 'condition' => 'baik', 'location_id' => $labSentrifugasi],
            ['item_id' => $sentrifus->id, 'serial_number' => 'SNF-002', 'asset_tag' => 'AT-SNF-002', 'condition' => 'baik', 'location_id' => $labSentrifugasi],
        ];

        foreach ($units as $unit) {
            ItemUnit::create(array_merge($unit, [
                'purchase_date' => now()->subYears(2),
                'last_calibration_date' => now()->subMonths(6),
                'next_calibration_date' => now()->addMonths(6),
                'notes' => null,
                'created_by' => $createdBy,
            ]));
        }

        $this->command->info('Created '.ItemUnit::count().' item units');
    }
}
