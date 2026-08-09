<?php

namespace Database\Seeders;

use App\Models\AuditTrail;
use App\Models\ItemCalibration;
use App\Models\ItemMaintenance;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalibrationMaintenanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo 'CalibrationMaintenanceSeeder started at '.now()->toDateTimeString().PHP_EOL;
        $users = User::all();
        $admin = $users->where('role', 'laboran')->first() ?? $users->first();
        $staff = $users->where('role', 'peminjam')->first() ?? $users->last();

        // Get item units that need calibration/maintenance
        $itemUnits = ItemUnit::whereHas('item.category', function ($q) {
            $q->where('type', 'alat');
        })->get();

        if ($itemUnits->isEmpty()) {
            $this->command->error('No item units found for calibration/maintenance');

            return;
        }

        // Create calibration records
        $this->createCalibrationRecords($itemUnits, $admin, $staff);

        // Create maintenance records (some from damage, some routine)
        $this->createMaintenanceRecords($itemUnits, $admin, $staff);

        $this->command->info('Created calibration and maintenance records');
        echo 'CalibrationMaintenanceSeeder finished at '.now()->toDateTimeString().PHP_EOL;
    }

    /**
     * Create calibration records for item units
     */
    private function createCalibrationRecords($itemUnits, $admin, $staff)
    {
        foreach ($itemUnits as $unit) {
            // Create 1-3 calibration records per unit
            $calibrationCount = rand(1, 3);

            for ($i = 0; $i < $calibrationCount; $i++) {
                // Create calibration record in chronological order
                $daysAgo = rand(30, 365) * ($i + 1); // Spread over time
                $calibrationDate = now()->subDays($daysAgo);
                $nextCalibrationDate = $calibrationDate->copy()->addMonths(6); // 6 month interval

                $calibration = ItemCalibration::create([
                    'item_unit_id' => $unit->id,
                    'calibration_date' => $calibrationDate,
                    'next_calibration_date' => $nextCalibrationDate,
                    'calibrated_by' => fake()->name(),
                    'certificate_number' => fake()->optional(0.9)->uuid(),
                    'result' => fake()->randomElement(['lulus', 'tidak_lulus']),
                    'notes' => fake()->optional(0.5)->sentence(),
                    'recorded_by' => in_array($i, [0, $calibrationCount - 1]) ? $admin->id : $staff->id,
                ]);

                // Update unit's calibration dates (last one wins)
                if ($i === $calibrationCount - 1) { // Last calibration
                    $unit->update([
                        'last_calibration_date' => $calibration->calibration_date,
                        'next_calibration_date' => $calibration->next_calibration_date,
                        'notes' => $unit->notes.' Diperbarui: Hasil kalibrasi '.
                                  ($calibration->result === 'lulus' ? 'LULUS' : 'TIDAK LULUS').
                                  ' tanggal '.$calibration->calibration_date->format('d/m/Y').' ',
                    ]);
                }

                // Create audit trail
                AuditTrail::create([
                    'user_id' => $calibration->recorded_by,
                    'auditable_type' => ItemCalibration::class,
                    'auditable_id' => $calibration->id,
                    'action' => 'created',
                    'new_values' => $calibration->toArray(),
                ]);

                // If calibration failed, create a maintenance record
                if ($calibration->result === 'tidak_lulus') {
                    $maintenance = ItemMaintenance::create([
                        'item_unit_id' => $unit->id,
                        'maintenance_date' => $calibration->calibration_date->copy()->addDay(),
                        'description' => 'Perbaikan sebagai hasil kalibrasi yang tidak lulus',
                        'performed_by' => 'Teknik Kalibrasi Internal',
                        'cost' => rand(200000, 1000000),
                        'status' => 'selesai',
                        'notes' => 'Perbaikan dilakukan setelah kalibrasi menunjukkan impak pada akurasi alat.',
                        'recorded_by' => $admin->id,
                    ]);

                    // Update unit after maintenance
                    $unit->update([
                        'condition' => 'baik', // Assume fixed
                        'last_calibration_date' => $maintenance->maintenance_date,
                        'next_calibration_date' => $maintenance->maintenance_date->copy()->addMonths(6),
                        'notes' => $unit->notes.' Diperbarui: Maintenance setelah kalibrasi gagal, kini kondisi baik.',
                    ]);

                    // Audit trail for maintenance
                    AuditTrail::create([
                        'user_id' => $maintenance->recorded_by,
                        'auditable_type' => ItemMaintenance::class,
                        'auditable_id' => $maintenance->id,
                        'action' => 'created',
                        'new_values' => $maintenance->toArray(),
                    ]);
                }
            }
        }
    }

    /**
     * Create maintenance records (routine and damage-related)
     */
    private function createMaintenanceRecords($itemUnits, $admin, $staff)
    {
        foreach ($itemUnits as $unit) {
            // Create 0-2 maintenance records per unit (excluding those from calibration failures)
            $maintenanceCount = rand(0, 2);

            for ($i = 0; $i < $maintenanceCount; $i++) {
                // Skip if we already created maintenance from calibration failure in this unit
                // For simplicity, we'll just create them

                $daysAgo = rand(10, 200) * ($i + 1);
                $maintenanceDate = now()->subDays($daysAgo);

                $maintenance = ItemMaintenance::create([
                    'item_unit_id' => $unit->id,
                    'maintenance_date' => $maintenanceDate,
                    'description' => fake()->randomElement([
                        'Pembersihan dan pelumas rutin mekanisme fokus',
                        'Penggantian lampu sumber iluminasi',
                        'Kalibrasi ulangステージ efter pergantian komponenten',
                        'Pengecekan och perbaiking sistem pendingin',
                        'Pemutakhiran firmware och programvara kontroll',
                        'Perbaika sambandning elström och jordning',
                    ]),
                    'performed_by' => fake()->randomElement([
                        'Teknik Laboratorium Senior',
                        'Vendor Official Service',
                        'Teknik Kalibrasi Internal',
                        'Tim Maintenance Terkait',
                    ]),
                    'cost' => rand(150000, 2000000),
                    'status' => fake()->randomElement(['selesai', 'proses', 'tertunda']),
                    'notes' => fake()->optional(0.5)->sentence(),
                    'recorded_by' => $admin->id,
                ]);

                // Update unit if maintenance is completed
                if ($maintenance->status === 'selesai') {
                    $unit->update([
                        'condition' => 'baik',
                        'notes' => $unit->notes.' Diperbarui: Maintenance selesai tanggal '.
                                 $maintenance->maintenance_date->format('d/m/Y').'. Kondisi återgår bra.',
                    ]);
                }

                // Create audit trail
                AuditTrail::create([
                    'user_id' => $maintenance->recorded_by,
                    'auditable_type' => ItemMaintenance::class,
                    'auditable_id' => $maintenance->id,
                    'action' => 'created',
                    'new_values' => $maintenance->toArray(),
                ]);
            }
        }
    }
}
