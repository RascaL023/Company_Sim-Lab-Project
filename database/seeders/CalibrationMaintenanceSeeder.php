<?php

namespace Database\Seeders;

use App\Models\AuditTrail;
use App\Models\ItemCalibration;
use App\Models\ItemMaintenance;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CalibrationMaintenanceSeeder extends Seeder
{
    /**
     * Kalibrasi deterministik per unit alat (semua lulus) + satu maintenance rutin.
     */
    public function run(): void
    {
        $laboran = User::where('role', 'laboran')->value('id') ?? User::first()->id;

        $units = ItemUnit::whereHas('item.category', function ($q) {
            $q->where('type', 'alat');
        })->get();

        if ($units->isEmpty()) {
            $this->command->error('Tidak ada unit alat untuk kalibrasi/maintenance');

            return;
        }

        $i = 0;
        foreach ($units as $unit) {
            $calibration = ItemCalibration::create([
                'item_unit_id' => $unit->id,
                'calibration_date' => Carbon::parse('-6 months')->addDays($i),
                'next_calibration_date' => Carbon::parse('+6 months')->addDays($i),
                'calibrated_by' => 'Teknik Kalibrasi Internal',
                'certificate_number' => 'CAL-'.str_pad((string) $unit->id, 4, '0', STR_PAD_LEFT),
                'result' => 'lulus',
                'notes' => 'Kalibrasi rutin, hasil LULUS.',
                'recorded_by' => $laboran,
            ]);

            AuditTrail::create([
                'user_id' => $laboran,
                'auditable_type' => ItemCalibration::class,
                'auditable_id' => $calibration->id,
                'action' => 'created',
                'new_values' => $calibration->toArray(),
            ]);

            $i++;
        }

        // Satu maintenance rutin untuk unit CX23-001.
        $cx23001 = ItemUnit::where('serial_number', 'CX23-001')->first();
        if ($cx23001) {
            $maintenance = ItemMaintenance::create([
                'item_unit_id' => $cx23001->id,
                'maintenance_date' => Carbon::parse('-3 months'),
                'description' => 'Pembersihan dan pelumasan mekanisme fokus',
                'performed_by' => 'Teknik Laboratorium Internal',
                'cost' => 250000,
                'status' => 'selesai',
                'notes' => 'Rutin perawatan berkala, kondisi normal.',
                'recorded_by' => $laboran,
            ]);

            AuditTrail::create([
                'user_id' => $laboran,
                'auditable_type' => ItemMaintenance::class,
                'auditable_id' => $maintenance->id,
                'action' => 'created',
                'new_values' => $maintenance->toArray(),
            ]);
        }

        $this->command->info('Created calibration and maintenance records');
    }
}
