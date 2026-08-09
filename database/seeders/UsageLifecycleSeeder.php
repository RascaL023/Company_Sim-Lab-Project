<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsageLifecycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $admin = $users->where('role', 'laboran')->first() ?? $users->first();
        $staff = $users->where('role', 'peminjam')->first() ?? $users->last();

        // Get bahan items (consumables that get used up)
        $bahanItems = Item::whereHas('category', function ($q) {
            $q->where('type', 'bahan');
        })->where('stock_quantity', '>', 0)->get();

        if ($bahanItems->isEmpty()) {
            $this->command->error('No bahan items with stock found for usage');

            return;
        }

        // Scenario 1: Normal usage and verification
        $this->createNormalUsageCycle($bahanItems, $admin, $staff);

        // Scenario 2: Usage that gets rejected (discrepancy)
        $this->createRejectedUsage($bahanItems, $admin, $staff);

        // Scenario 3: High volume usage that triggers reorder
        $this->createHighVolumeUsage($bahanItems, $admin, $staff);

        $this->command->info('Created usage lifecycle records');
    }

    /**
     * Create a normal usage cycle: usage -> verification
     */
    private function createNormalUsageCycle($bahanItems, $admin, $staff)
    {
        // Pick a random bahan item
        $item = $bahanItems->random();

        // Record some usage
        $quantityUsed = fake()->randomFloat(2, 5, 50);
        $quantityBefore = $item->stock_quantity;
        $quantityAfter = max(0, $quantityBefore - $quantityUsed);

        // 1. Create usage record (initially recorded)
        $usage = Usage::create([
            'item_id' => $item->id,
            'item_unit_id' => null, // bahan doesn't use unit tracking
            'user_id' => $staff->id,
            'verified_by' => null,
            'quantity_used' => $quantityUsed,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'usage_date' => now()->subDays(5),
            'status' => 'dicatat',
            'rejection_reason' => null,
            'verified_at' => null,
            'purpose' => 'Analisis rutin sampel air',
            'notes' => 'Penggunaan sesuai dengan SOP analisis.',
        ]);

        // 2. Verify the usage (admin checks and confirms)
        $usage->update([
            'verified_by' => $admin->id,
            'status' => 'diverifikasi',
            'verified_at' => now()->subDays(4),
        ]);

        // 3. Update item stock quantity
        $item->update([
            'stock_quantity' => $quantityAfter,
        ]);

        // 4. Create stock movement record (outgoing)
        StockMovement::create([
            'item_id' => $item->id,
            'item_unit_id' => null,
            'type' => 'out_usage',
            'quantity' => $quantityUsed,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => Usage::class,
            'reference_id' => $usage->id,
            'performed_by' => $staff->id,
            'notes' => 'Penggunaan bahan untuk analisis sampel air',
            'occurred_at' => $usage->usage_date,
        ]);

        // 5. Create audit trails
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => Usage::class,
            'auditable_id' => $usage->id,
            'action' => 'created',
            'new_values' => $usage->toArray(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => Usage::class,
            'auditable_id' => $usage->id,
            'action' => 'verified',
            'old_values' => ['status' => 'dicatat', 'verified_by' => null],
            'new_values' => ['status' => 'diverifikasi', 'verified_by' => $admin->id],
        ]);

        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => Item::class,
            'auditable_id' => $item->id,
            'action' => 'updated',
            'old_values' => ['stock_quantity' => $quantityBefore],
            'new_values' => ['stock_quantity' => $quantityAfter],
        ]);

        // 6. Create attachment (usage record/document)
        Attachment::create([
            'attachable_type' => Usage::class,
            'attachable_id' => $usage->id,
            'type' => 'usage_record',
            'file_path' => 'usages/record_'.$usage->id.'.pdf',
            'original_filename' => 'usage_record.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 512000,
            'disk' => 'public',
            'description' => 'Rekord penggunaan bahan untuk analisis',
            'uploaded_by' => $staff->id,
        ]);
    }

    /**
     * Create a usage that gets rejected during verification (discrepancy)
     */
    private function createRejectedUsage($bahanItems, $admin, $staff)
    {
        // Pick a random bahan item
        $item = $bahanItems->random();

        // Record usage with discrepancy
        $quantityUsed = fake()->randomFloat(2, 10, 100); // Claimed usage
        $quantityBefore = $item->stock_quantity;
        $actualQuantityUsed = fake()->randomFloat(2, 5, 20); // Actual usage found during verification
        $quantityAfter = max(0, $quantityBefore - $actualQuantityUsed);

        // 1. Create usage record (initially recorded)
        $usage = Usage::create([
            'item_id' => $item->id,
            'item_unit_id' => null,
            'user_id' => $staff->id,
            'verified_by' => null,
            'quantity_used' => $quantityUsed, // This is the claimed/discrepant amount
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'usage_date' => now()->subDays(3),
            'status' => 'dicatat',
            'rejection_reason' => null,
            'verified_at' => null,
            'purpose' => 'Persiapan reagent untuk batch analisis',
            'notes' => 'Penggunaan bahan untuk persiapan reagent.',
        ]);

        // 2. Reject the usage during verification (amount doesn't match)
        $usage->update([
            'verified_by' => $admin->id,
            'status' => 'ditolak',
            'verified_at' => now()->subDays(2),
            'rejection_reason' => 'Jumlah penggunaan yang dilaporkan ('.$quantityUsed.' '.$item->unit.') tidak sesuai dengan pengukuran stok yang dilakukan. Stok sebelum: '.$quantityBefore.', Stok sesudah seharusnya: '.($quantityBefore - $quantityUsed).', Stok sesudah aktual: '.$quantityAfter.'. Selisih menunjukkan penggunaan aktual sebesar '.$actualQuantityUsed.' '.$item->unit.'.',
        ]);

        // 3. Note: Item stock is NOT updated because usage was rejected
        // The stock remains as it was before the rejected usage

        // 4. Create audit trails
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => Usage::class,
            'auditable_id' => $usage->id,
            'action' => 'created',
            'new_values' => $usage->toArray(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => Usage::class,
            'auditable_id' => $usage->id,
            'action' => 'rejected',
            'old_values' => ['status' => 'dicatat', 'rejection_reason' => null],
            'new_values' => [
                'status' => 'ditolak',
                'verified_by' => $admin->id,
                'verified_at' => $usage->verified_at,
                'rejection_reason' => $usage->rejection_reason,
            ],
        ]);

        $this->command->info('Created rejected usage record');
    }

    /**
     * Create high volume usage that might trigger reorder alerts
     */
    private function createHighVolumeUsage($bahanItems, $admin, $staff)
    {
        // Pick a random bahan item
        $item = $bahanItems->random();

        // Use a large quantity
        $quantityUsed = min($item->stock_quantity * 0.8, fake()->randomFloat(2, 50, 200)); // Use up to 80% of stock
        $quantityBefore = $item->stock_quantity;
        $quantityAfter = max(0, $quantityBefore - $quantityUsed);

        // 1. Create usage record
        $usage = Usage::create([
            'item_id' => $item->id,
            'item_unit_id' => null,
            'user_id' => $staff->id,
            'verified_by' => $admin->id, // Verify immediately
            'quantity_used' => $quantityUsed,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'usage_date' => now()->subDays(1),
            'status' => 'diverifikasi',
            'rejection_reason' => null,
            'verified_at' => now()->subDays(1),
            'purpose' => 'Persiapan untuk analisis batch besar',
            'notes' => 'Penggunaan bahan dalam jumlah besar untuk proyek risiko.',
        ]);

        // 2. Update item stock quantity
        $item->update([
            'stock_quantity' => $quantityAfter,
        ]);

        // 3. Create stock movement record
        StockMovement::create([
            'item_id' => $item->id,
            'item_unit_id' => null,
            'type' => 'out_usage',
            'quantity' => $quantityUsed,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => Usage::class,
            'reference_id' => $usage->id,
            'performed_by' => $staff->id,
            'notes' => 'Penggunaan bahan dalam volume tinggi untuk analisis batch',
            'occurred_at' => $usage->usage_date,
        ]);

        // 4. Create audit trails
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => Usage::class,
            'auditable_id' => $usage->id,
            'action' => 'created',
            'new_values' => $usage->toArray(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => Usage::class,
            'auditable_id' => $usage->id,
            'action' => 'verified',
            'old_values' => ['status' => 'dicatat', 'verified_by' => null],
            'new_values' => ['status' => 'diverifikasi', 'verified_by' => $admin->id],
        ]);

        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => Item::class,
            'auditable_id' => $item->id,
            'action' => 'updated',
            'old_values' => ['stock_quantity' => $quantityBefore],
            'new_values' => ['stock_quantity' => $quantityAfter],
        ]);

        $this->command->info('Created high volume usage record');
    }
}
