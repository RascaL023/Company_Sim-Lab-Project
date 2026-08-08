<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\AuditTrail;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Database\Seeder;

class BorrowingLifecycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $admin = $users->where('role', 'admin')->first() ?? $users->first();
        $staff = $users->where('role', 'staf')->first() ?? $users->last();

        // Get some alat items with units
        $alatItemsWithUnits = Item::whereHas('category', function ($q) {
            $q->where('type', 'alat');
        })->whereHas('units', function ($q) {
            $q->where('condition', 'baik');
        })->get();

        if ($alatItemsWithUnits->isEmpty()) {
            $this->command->error('No alat items with units found for borrowing');

            return;
        }

        // Scenario 1: Normal borrowing and return
        $this->createNormalBorrowingCycle($alatItemsWithUnits, $admin, $staff);

        // Scenario 2: Borrowing with damage -> maintenance record
        $this->createDamageBorrowingCycle($alatItemsWithUnits, $admin, $staff);

        // Scenario 3: Overdue borrowing
        $this->createOverdueBorrowing($alatItemsWithUnits, $admin, $staff);

        // Scenario 4: Request rejected
        $this->createRejectedRequest($alatItemsWithUnits, $admin, $staff);

        $this->command->info('Created borrowing lifecycle records');
    }

    /**
     * Create a normal borrowing request -> approval -> borrow -> return cycle
     */
    private function createNormalBorrowingCycle($alatItems, $admin, $staff)
    {
        // Pick a random item and unit
        $item = $alatItems->random();
        $unit = $item->units()->where('condition', 'baik')->first();

        if (! $unit) {
            return;
        }

        // 1. Create borrowing request
        $request = BorrowingRequest::create([
            'request_number' => 'BR-'.now()->format('Y').sprintf('%04d', rand(1000, 9999)),
            'requested_by' => $staff->id,
            'approved_by' => $admin->id,
            'status' => 'disetujui',
            'purpose' => 'Pengujian rutin bahan kimia',
            'requested_at' => now()->subDays(5),
            'approved_at' => now()->subDays(4),
        ]);

        // 2. Create borrowing item
        $borrowingItem = BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => 'baik',
            'is_damaged' => false,
            'borrow_date' => now()->subDays(3),
            'expected_return_date' => now()->addDays(2),
            'actual_return_date' => now()->subHours(2),
            'checked_by' => $admin->id,
            'checked_at' => now()->subHours(1),
            'check_notes' => 'Barang dikembalikan dalam kondisi baik, tidak ada kerusakan terlihat.',
        ]);

        // 3. Create borrowing transaction (checkout/checkin)
        Borrowing::create([
            'borrowing_item_id' => $borrowingItem->id,
            'borrower_id' => $staff->id,
            'borrow_date' => $borrowingItem->borrow_date,
            'expected_return_date' => $borrowingItem->expected_return_date,
            'actual_return_date' => $borrowingItem->actual_return_date,
            'condition_before' => $borrowingItem->condition_before,
            'condition_after' => $borrowingItem->condition_after,
            'is_damaged' => $borrowingItem->is_damaged,
            'damage_notes' => $borrowingItem->damage_notes,
            'checked_out_by' => $admin->id,
            'checked_in_by' => $admin->id,
            'checked_by' => $borrowingItem->checked_by,
            'checked_at' => $borrowingItem->checked_at,
            'check_notes' => $borrowingItem->check_notes,
            'status' => 'dikembalikan',
            'notes' => 'Peminjaman selesai tanpa masalah.',
        ]);

        // 4. Update unit condition if needed (should remain baik)
        $unit->refresh();

        // 5. Create audit trails
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'created',
            'new_values' => $request->toArray(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'approved',
            'old_values' => ['status' => 'diajukan'],
            'new_values' => ['status' => 'disetujui'],
        ]);

        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => BorrowingItem::class,
            'auditable_id' => $borrowingItem->id,
            'action' => 'updated',
            'old_values' => ['actual_return_date' => null],
            'new_values' => ['actual_return_date' => $borrowingItem->actual_return_date],
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => Borrowing::class,
            'auditable_id' => Borrowing::where('borrowing_item_id', $borrowingItem->id)->first()->id,
            'action' => 'checked_in',
            'new_values' => [
                'actual_return_date' => $borrowingItem->actual_return_date,
                'checked_by' => $admin->id,
                'status' => 'dikembalikan',
            ],
        ]);

        // 6. Create attachment (condition evidence)
        // Note: We're not actually creating files, just database records
        Attachment::create([
            'attachable_type' => BorrowingItem::class,
            'attachable_id' => $borrowingItem->id,
            'type' => 'condition_after',
            'file_path' => 'borrowings/condition_after_'.$borrowingItem->id.'.jpg',
            'original_filename' => 'condition_after.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 204800,
            'disk' => 'public',
            'description' => 'Foto kondisi barang setelah pemakaian',
            'uploaded_by' => $admin->id,
        ]);
    }

    /**
     * Create a borrowing where item is returned damaged -> creates maintenance record
     */
    private function createDamageBorrowingCycle($alatItems, $admin, $staff)
    {
        // Pick a random item and unit
        $item = $alatItems->random();
        $unit = $item->units()->where('condition', 'baik')->first();

        if (! $unit) {
            return;
        }

        // 1. Create borrowing request
        $request = BorrowingRequest::create([
            'request_number' => 'BR-'.now()->format('Y').sprintf('%04d', rand(1000, 9999)),
            'requested_by' => $staff->id,
            'approved_by' => $admin->id,
            'status' => 'disetujui',
            'purpose' => 'Pengujian bahan korosif',
            'requested_at' => now()->subDays(10),
            'approved_at' => now()->subDays(9),
        ]);

        // 2. Create borrowing item (will be returned damaged)
        $borrowingItem = BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => 'rusak_berat', // Returned damaged
            'is_damaged' => true,
            'damage_notes' => 'Terjadi retakan pada bagian housing mikroskop akibat jatuh selama pengujian.',
            'borrow_date' => now()->subDays(7),
            'expected_return_date' => now()->subDays(2),
            'actual_return_date' => now()->subDays(1),
            'checked_by' => $admin->id,
            'checked_at' => now()->subHours(12),
            'check_notes' => 'Barang dikembalikan dengan kerusakan berat pada housing bagian kiri. Disarankan perbaikan.',
        ]);

        // 3. Create borrowing transaction
        Borrowing::create([
            'borrowing_item_id' => $borrowingItem->id,
            'borrower_id' => $staff->id,
            'borrow_date' => $borrowingItem->borrow_date,
            'expected_return_date' => $borrowingItem->expected_return_date,
            'actual_return_date' => $borrowingItem->actual_return_date,
            'condition_before' => $borrowingItem->condition_before,
            'condition_after' => $borrowingItem->condition_after,
            'is_damaged' => $borrowingItem->is_damaged,
            'damage_notes' => $borrowingItem->damage_notes,
            'checked_out_by' => $admin->id,
            'checked_in_by' => $admin->id,
            'checked_by' => $borrowingItem->checked_by,
            'checked_at' => $borrowingItem->checked_at,
            'check_notes' => $borrowingItem->check_notes,
            'status' => 'dikembalikan',
            'notes' => 'Barang dikembalikan dalam keadaan rusak, perlu perbaikan.',
        ]);

        // 4. Update unit condition to reflect damage
        $unit->update([
            'condition' => 'rusak_berat',
            'notes' => $unit->notes.' Diperbarui: Rusak berat akibat jatuh selama peminjaman ['.now()->toDateString().']',
        ]);

        // 5. Create maintenance record (triggered by damage)
        $maintenance = ItemMaintenance::create([
            'item_unit_id' => $unit->id,
            'maintenance_date' => now()->subDays(1),
            'description' => 'Perbaikan housing mikroskop akibat retakan dari jatuh selama peminjaman',
            'performed_by' => 'Teknik Laboratorium Internal',
            'cost' => 750000,
            'status' => 'selesai',
            'notes' => 'Perbaikan selesai: penggantian bagian housing kiri dan pengujian fungsional ulang.',
            'recorded_by' => $admin->id,
        ]);

        // 6. Update unit condition after maintenance
        $unit->update([
            'condition' => 'baik',
            'last_calibration_date' => now()->subDays(1),
            'next_calibration_date' => now()->addMonths(6),
            'notes' => $unit->notes.' Diperbarui: Setelah perbaikan dan kalibrasi ulang ['.now()->toDateString().']',
        ]);

        // 7. Create audit trails
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'created',
            'new_values' => $request->toArray(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'approved',
            'old_values' => ['status' => 'diajukan'],
            'new_values' => ['status' => 'disetujui'],
        ]);

        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => BorrowingItem::class,
            'auditable_id' => $borrowingItem->id,
            'action' => 'updated',
            'old_values' => ['condition_after' => 'baik', 'is_damaged' => false],
            'new_values' => ['condition_after' => 'rusak_berat', 'is_damaged' => true, 'damage_notes' => $borrowingItem->damage_notes],
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => ItemMaintenance::class,
            'auditable_id' => $maintenance->id,
            'action' => 'created',
            'new_values' => $maintenance->toArray(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => ItemUnit::class,
            'auditable_id' => $unit->id,
            'action' => 'updated',
            'old_values' => ['condition' => 'rusak_berat'],
            'new_values' => ['condition' => 'baik'],
        ]);
    }

    /**
     * Create an overdue borrowing (not returned on time)
     */
    private function createOverdueBorrowing($alatItems, $admin, $staff)
    {
        // Pick a random item and unit
        $item = $alatItems->random();
        $unit = $item->units()->where('condition', 'baik')->first();

        if (! $unit) {
            return;
        }

        // 1. Create borrowing request
        $request = BorrowingRequest::create([
            'request_number' => 'BR-'.now()->format('Y').sprintf('%04d', rand(1000, 9999)),
            'requested_by' => $staff->id,
            'approved_by' => $admin->id,
            'status' => 'diproses', // Still in process
            'purpose' => 'Penelitian jangka panjang',
            'requested_at' => now()->subDays(20),
            'approved_at' => now()->subDays(19),
        ]);

        // 2. Create borrowing item (not returned yet)
        $borrowingItem = BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => null,
            'is_damaged' => false,
            'borrow_date' => now()->subDays(15),
            'expected_return_date' => now()->subDays(5), // Already overdue
            'actual_return_date' => null, // Not returned yet
            'checked_by' => null,
            'checked_at' => null,
            'check_notes' => null,
        ]);

        // 3. Create borrowing transaction (only checked out)
        Borrowing::create([
            'borrowing_item_id' => $borrowingItem->id,
            'borrower_id' => $staff->id,
            'borrow_date' => $borrowingItem->borrow_date,
            'expected_return_date' => $borrowingItem->expected_return_date,
            'actual_return_date' => null,
            'condition_before' => $borrowingItem->condition_before,
            'condition_after' => null,
            'is_damaged' => false,
            'damage_notes' => null,
            'checked_out_by' => $admin->id,
            'checked_in_by' => null,
            'checked_by' => null,
            'checked_at' => null,
            'check_notes' => null,
            'status' => 'terlambat',
            'notes' => 'Barang belum dikembalikan melebihi batas waktu yang ditentukan.',
        ]);

        // 4. Create audit trails
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'created',
            'new_values' => $request->toArray(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'approved',
            'old_values' => ['status' => 'diajukan'],
            'new_values' => ['status' => 'disetujui'],
        ]);

        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => Borrowing::class,
            'auditable_id' => Borrowing::where('borrowing_item_id', $borrowingItem->id)->first()->id,
            'action' => 'checked_out',
            'new_values' => [
                'borrow_date' => $borrowingItem->borrow_date,
                'expected_return_date' => $borrowingItem->expected_return_date,
                'checked_out_by' => $admin->id,
                'status' => 'dipinjam',
            ],
        ]);
    }

    /**
     * Create a request that gets rejected
     */
    private function createRejectedRequest($alatItems, $admin, $staff)
    {
        // Pick a random item and unit
        $item = $alatItems->random();
        $unit = $item->units()->where('condition', 'baik')->first();

        if (! $unit) {
            return;
        }

        // 1. Create borrowing request that gets rejected
        $request = BorrowingRequest::create([
            'request_number' => 'BR-'.now()->format('Y').sprintf('%04d', rand(1000, 9999)),
            'requested_by' => $staff->id,
            'approved_by' => $admin->id, // Still set but will be overridden by rejection
            'status' => 'ditolak',
            'purpose' => 'Pengujian dengan bahan berbahaya yang tidak diizinkan',
            'requested_at' => now()->subDays(3),
            'approved_at' => now()->subDays(2), // When it was processed/rejected
            'rejected_at' => now()->subDays(2),
            'rejection_reason' => 'Pengajuan ditolak karena bahan yang akan digunakan tergolong berbahaya dan memerlukan izin khusus yang belum ada.',
        ]);

        // 2. Create borrowing item (never approved, so never borrowed)
        $borrowingItem = BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => null,
            'is_damaged' => false,
            'borrow_date' => null, // Never borrowed
            'expected_return_date' => null,
            'actual_return_date' => null,
            'checked_by' => null,
            'checked_at' => null,
            'check_notes' => null,
        ]);

        // 3. Create audit trails
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'created',
            'new_values' => $request->toArray(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'rejected',
            'old_values' => ['status' => 'diajukan'],
            'new_values' => ['status' => 'ditolak', 'rejection_reason' => $request->rejection_reason],
        ]);

        $this->command->info('Created rejected borrowing request');
    }
}
