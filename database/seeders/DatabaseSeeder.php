<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ItemSeeder::class,
            LocationSeeder::class,
            ItemUnitSeeder::class,
            BorrowingLifecycleSeeder::class,
            UsageLifecycleSeeder::class,
            StockMovementSeeder::class,
            CalibrationMaintenanceSeeder::class,
            AuditTrailSeeder::class,
            AttachmentSeeder::class,
        ]);
    }
}
