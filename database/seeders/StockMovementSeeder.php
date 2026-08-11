<?php

namespace Database\Seeders;

use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StockMovementSeeder extends Seeder
{
    /**
     * Buku besar stok yang deterministik dan konsisten secara matematis:
     *
     *   after = before + quantity  (incoming)
     *   after = before - quantity  (outgoing)
     *   after >= 0
     *
     * Untuk tiap bahan dibuat rantai pergerakan kronologis; stok akhir item
     * diset sama dengan nilai after pergerakan terakhir. Setiap pergerakan
     * out_usage disertai Usage yang sudah diverifikasi dengan angka yang sama.
     */
    public function run(): void
    {
        $laboran = User::where('role', 'laboran')->value('id') ?? User::first()->id;
        $peminjam = User::where('role', 'peminjam')->value('id') ?? User::first()->id;

        $ledgers = [
            'HCL-001' => [
                ['type' => 'in_purchase', 'quantity' => 100.0, 'occurred_at' => '-120 days', 'notes' => 'Pembelian awal Asam Klorida 37% dari supplier.'],
                ['type' => 'in_purchase', 'quantity' => 50.0, 'occurred_at' => '-90 days', 'notes' => 'Pengadaan tambahan stok Asam Klorida 37%.'],
                ['type' => 'out_usage', 'quantity' => 20.0, 'occurred_at' => '-60 days', 'notes' => 'Pemakaian untuk analisis rutin sampel air.', 'purpose' => 'Analisis rutin sampel air'],
                ['type' => 'out_usage', 'quantity' => 10.0, 'occurred_at' => '-30 days', 'notes' => 'Pemakaian untuk persiapan reagen titrasi.', 'purpose' => 'Persiapan reagen titrasi'],
            ],
            'ETN-001' => [
                ['type' => 'in_purchase', 'quantity' => 60.0, 'occurred_at' => '-60 days', 'notes' => 'Pembelian awal Ethanol 96%.'],
                ['type' => 'out_usage', 'quantity' => 10.0, 'occurred_at' => '-20 days', 'notes' => 'Pemakaian untuk sterilisasi alat.', 'purpose' => 'Sterilisasi alat gelas'],
            ],
            'TIP-001' => [
                ['type' => 'in_purchase', 'quantity' => 50.0, 'occurred_at' => '-40 days', 'notes' => 'Pembelian awal tip pipet steril.'],
                ['type' => 'out_usage', 'quantity' => 10.0, 'occurred_at' => '-10 days', 'notes' => 'Pemakaian untuk praktikum.', 'purpose' => 'Praktikum laboratorium'],
            ],
        ];

        $total = 0;

        foreach ($ledgers as $itemCode => $entries) {
            $item = Item::where('code', $itemCode)->first();
            if (! $item) {
                $this->command->warn("Item {$itemCode} tidak ditemukan, lewati.");

                continue;
            }

            $running = 0.0;

            foreach ($entries as $entry) {
                $isIncoming = str_starts_with($entry['type'], 'in_');
                $before = $running;
                $after = $isIncoming ? $before + $entry['quantity'] : $before - $entry['quantity'];
                $running = $after;

                $movement = StockMovement::create([
                    'item_id' => $item->id,
                    'item_unit_id' => null,
                    'type' => $entry['type'],
                    'quantity' => $entry['quantity'],
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reference_type' => null,
                    'reference_id' => null,
                    'performed_by' => $entry['type'] === 'out_usage' ? $peminjam : $laboran,
                    'notes' => $entry['notes'],
                    'occurred_at' => Carbon::parse($entry['occurred_at']),
                ]);

                if ($entry['type'] === 'out_usage') {
                    $usage = Usage::create([
                        'item_id' => $item->id,
                        'item_unit_id' => null,
                        'user_id' => $peminjam,
                        'verified_by' => $laboran,
                        'quantity_used' => $entry['quantity'],
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'usage_date' => $movement->occurred_at,
                        'status' => 'diverifikasi',
                        'verified_at' => $movement->occurred_at->copy()->addDay(),
                        'purpose' => $entry['purpose'],
                        'notes' => $entry['notes'],
                    ]);

                    $movement->update([
                        'reference_type' => Usage::class,
                        'reference_id' => $usage->id,
                    ]);
                }

                AuditTrail::create([
                    'user_id' => $movement->performed_by,
                    'auditable_type' => StockMovement::class,
                    'auditable_id' => $movement->id,
                    'action' => 'created',
                    'new_values' => $movement->toArray(),
                ]);

                $total++;
            }

            $item->update(['stock_quantity' => $running]);
        }

        $this->command->info("Created {$total} stock movement records");
    }
}
