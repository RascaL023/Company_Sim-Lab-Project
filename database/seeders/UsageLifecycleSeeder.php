<?php

namespace Database\Seeders;

use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class UsageLifecycleSeeder extends Seeder
{
    /**
     * Contoh siklus usage tambahan (selain yang sudah diverifikasi di
     * StockMovementSeeder): satu usage ditolak dan satu usage menunggu
     * verifikasi. Keduanya TIDAK mengubah stok (konsisten dengan stok akhir).
     */
    public function run(): void
    {
        $laboran = User::where('role', 'laboran')->value('id') ?? User::first()->id;
        $peminjam = User::where('role', 'peminjam')->value('id') ?? User::first()->id;

        $hcl = Item::where('code', 'HCL-001')->first();
        $etn = Item::where('code', 'ETN-001')->first();

        // 1. Usage ditolak karena jumlah tidak sesuai (stok tidak berubah).
        if ($hcl) {
            $stock = (float) $hcl->stock_quantity;

            $usage = Usage::create([
                'item_id' => $hcl->id,
                'item_unit_id' => null,
                'user_id' => $peminjam,
                'verified_by' => $laboran,
                'quantity_used' => 30,
                'quantity_before' => $stock,
                'quantity_after' => $stock,
                'usage_date' => Carbon::parse('-5 days'),
                'status' => 'ditolak',
                'rejection_reason' => 'Jumlah yang dilaporkan (30 liter) tidak sesuai dengan stok fisik (berkurang 10 liter).',
                'verified_at' => Carbon::parse('-4 days'),
                'purpose' => 'Persiapan reagen batch analisis',
                'notes' => 'Penggunaan tidak disetujui karena selisih jumlah.',
            ]);

            AuditTrail::create([
                'user_id' => $peminjam,
                'auditable_type' => Usage::class,
                'auditable_id' => $usage->id,
                'action' => 'created',
                'new_values' => $usage->toArray(),
            ]);

            AuditTrail::create([
                'user_id' => $laboran,
                'auditable_type' => Usage::class,
                'auditable_id' => $usage->id,
                'action' => 'rejected',
                'old_values' => ['status' => 'dicatat'],
                'new_values' => ['status' => 'ditolak', 'rejection_reason' => $usage->rejection_reason],
            ]);
        }

        // 2. Usage menunggu verifikasi (status dicatat, stok belum berubah).
        if ($etn) {
            $stock = (float) $etn->stock_quantity;

            $usage = Usage::create([
                'item_id' => $etn->id,
                'item_unit_id' => null,
                'user_id' => $peminjam,
                'verified_by' => null,
                'quantity_used' => 5,
                'quantity_before' => $stock,
                'quantity_after' => max(0, $stock - 5),
                'usage_date' => Carbon::parse('-1 day'),
                'status' => 'dicatat',
                'rejection_reason' => null,
                'verified_at' => null,
                'purpose' => 'Ekstraksi sampel praktikum',
                'notes' => 'Penggunaan tercatat, menunggu verifikasi laboran.',
            ]);

            AuditTrail::create([
                'user_id' => $peminjam,
                'auditable_type' => Usage::class,
                'auditable_id' => $usage->id,
                'action' => 'created',
                'new_values' => $usage->toArray(),
            ]);
        }

        $this->command->info('Created usage lifecycle records');
    }
}
