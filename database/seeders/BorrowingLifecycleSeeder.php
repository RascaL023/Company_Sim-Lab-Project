<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\AuditTrail;
use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BorrowingLifecycleSeeder extends Seeder
{
    /**
     * Enam skenario peminjaman deterministik untuk menguji workflow:
     * selesai normal, rusak->maintenance, terlambat, ditolak, diajukan, disetujui.
     */
    public function run(): void
    {
        $laboran = User::where('role', 'laboran')->value('id') ?? User::first()->id;
        $peminjam = User::where('role', 'peminjam')->value('id') ?? User::first()->id;

        $mikroskop = Item::where('code', 'MCS-001')->first();
        $sentrifus = Item::where('code', 'SNF-001')->first();

        $cx23001 = ItemUnit::where('serial_number', 'CX23-001')->first();
        $cx23002 = ItemUnit::where('serial_number', 'CX23-002')->first();
        $snf001 = ItemUnit::where('serial_number', 'SNF-001')->first();
        $snf002 = ItemUnit::where('serial_number', 'SNF-002')->first();

        if (! $mikroskop || ! $sentrifus || ! $cx23001 || ! $cx23002 || ! $snf001 || ! $snf002) {
            $this->command->error('Data item/unit alat tidak lengkap untuk skenario peminjaman');

            return;
        }

        $this->normalCompleted($mikroskop, $cx23001, $laboran, $peminjam);
        $this->damageThenMaintenance($sentrifus, $snf001, $laboran, $peminjam);
        $this->overdue($mikroskop, $cx23002, $laboran, $peminjam);
        $this->rejected($sentrifus, $snf002, $laboran, $peminjam);
        $this->pending($sentrifus, $snf001, $peminjam);
        $this->approvedNotCheckedOut($sentrifus, $snf002, $laboran, $peminjam);

        $this->command->info('Created borrowing lifecycle records');
    }

    private function request(string $number, array $attrs): BorrowingRequest
    {
        $request = BorrowingRequest::create(array_merge([
            'request_number' => $number,
            'requested_at' => now(),
        ], $attrs));

        AuditTrail::create([
            'user_id' => $request->requested_by,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'created',
            'new_values' => $request->toArray(),
        ]);

        return $request;
    }

    private function approve(BorrowingRequest $request, int $laboran, Carbon $at): void
    {
        $request->update(['approved_by' => $laboran, 'approved_at' => $at, 'status' => 'disetujui']);

        AuditTrail::create([
            'user_id' => $laboran,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'approved',
            'old_values' => ['status' => 'diajukan'],
            'new_values' => ['status' => 'disetujui'],
        ]);
    }

    private function normalCompleted($item, $unit, int $laboran, int $peminjam): void
    {
        $request = $this->request('BR-2026-0001', [
            'requested_by' => $peminjam,
            'status' => 'selesai',
            'purpose' => 'Pengujian rutin sampel air',
            'requested_at' => Carbon::parse('-12 days'),
            'approved_at' => Carbon::parse('-11 days'),
            'approved_by' => $laboran,
        ]);

        $borrowingItem = BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => 'baik',
            'is_damaged' => false,
            'borrow_date' => Carbon::parse('-10 days'),
            'expected_return_date' => Carbon::parse('-5 days'),
            'actual_return_date' => Carbon::parse('-2 days'),
            'checked_by' => $laboran,
            'checked_at' => Carbon::parse('-2 days')->addHours(2),
            'check_notes' => 'Barang dikembalikan dalam kondisi baik, tidak ada kerusakan.',
            'checked_out_by' => $laboran,
            'checked_in_by' => $laboran,
        ]);

        AuditTrail::create([
            'user_id' => $laboran,
            'auditable_type' => BorrowingItem::class,
            'auditable_id' => $borrowingItem->id,
            'action' => 'checked_in',
            'new_values' => ['actual_return_date' => $borrowingItem->actual_return_date, 'checked_by' => $laboran],
        ]);

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
            'uploaded_by' => $laboran,
        ]);
    }

    private function damageThenMaintenance($item, $unit, int $laboran, int $peminjam): void
    {
        $request = $this->request('BR-2026-0002', [
            'requested_by' => $peminjam,
            'status' => 'selesai',
            'purpose' => 'Pengujian bahan korosif',
            'requested_at' => Carbon::parse('-25 days'),
            'approved_at' => Carbon::parse('-24 days'),
            'approved_by' => $laboran,
        ]);

        $borrowingItem = BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => 'rusak_berat',
            'is_damaged' => true,
            'damage_notes' => 'Retakan pada housing bagian kiri akibat jatuh selama pengujian.',
            'borrow_date' => Carbon::parse('-20 days'),
            'expected_return_date' => Carbon::parse('-15 days'),
            'actual_return_date' => Carbon::parse('-12 days'),
            'checked_by' => $laboran,
            'checked_at' => Carbon::parse('-12 days')->addDay(),
            'check_notes' => 'Kerusakan berat pada housing kiri, disarankan perbaikan.',
            'checked_out_by' => $laboran,
            'checked_in_by' => $laboran,
        ]);

        $unit->update(['condition' => 'rusak_berat']);

        $maintenance = ItemMaintenance::create([
            'item_unit_id' => $unit->id,
            'maintenance_date' => Carbon::parse('-11 days'),
            'description' => 'Perbaikan housing akibat retakan saat peminjaman',
            'performed_by' => 'Teknik Laboratorium Internal',
            'cost' => 750000,
            'status' => 'selesai',
            'notes' => 'Penggantian housing kiri dan uji fungsional ulang.',
            'recorded_by' => $laboran,
        ]);

        $unit->update([
            'condition' => 'baik',
            'last_calibration_date' => Carbon::parse('-11 days'),
            'next_calibration_date' => Carbon::parse('-11 days')->addMonths(6),
        ]);

        AuditTrail::create([
            'user_id' => $laboran,
            'auditable_type' => BorrowingItem::class,
            'auditable_id' => $borrowingItem->id,
            'action' => 'damaged_reported',
            'new_values' => ['condition_after' => 'rusak_berat', 'is_damaged' => true],
        ]);

        AuditTrail::create([
            'user_id' => $laboran,
            'auditable_type' => ItemMaintenance::class,
            'auditable_id' => $maintenance->id,
            'action' => 'created',
            'new_values' => $maintenance->toArray(),
        ]);
    }

    private function overdue($item, $unit, int $laboran, int $peminjam): void
    {
        $request = $this->request('BR-2026-0003', [
            'requested_by' => $peminjam,
            'status' => 'diproses',
            'purpose' => 'Penelitian jangka panjang',
            'requested_at' => Carbon::parse('-20 days'),
            'approved_at' => Carbon::parse('-19 days'),
            'approved_by' => $laboran,
        ]);

        $borrowingItem = BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => null,
            'is_damaged' => false,
            'borrow_date' => Carbon::parse('-15 days'),
            'expected_return_date' => Carbon::parse('-5 days'),
            'actual_return_date' => null,
            'checked_by' => null,
            'checked_at' => null,
            'check_notes' => null,
            'checked_out_by' => $laboran,
            'checked_in_by' => null,
        ]);

        AuditTrail::create([
            'user_id' => $laboran,
            'auditable_type' => BorrowingItem::class,
            'auditable_id' => $borrowingItem->id,
            'action' => 'checked_out',
            'new_values' => ['borrow_date' => $borrowingItem->borrow_date, 'expected_return_date' => $borrowingItem->expected_return_date],
        ]);
    }

    private function rejected($item, $unit, int $laboran, int $peminjam): void
    {
        $request = $this->request('BR-2026-0004', [
            'requested_by' => $peminjam,
            'status' => 'ditolak',
            'purpose' => 'Pengujian dengan bahan berbahaya tanpa izin khusus',
            'requested_at' => Carbon::parse('-4 days'),
            'approved_at' => Carbon::parse('-3 days'),
            'rejected_at' => Carbon::parse('-3 days'),
            'approved_by' => $laboran,
            'rejection_reason' => 'Penggunaan bahan berbahaya memerlukan izin khusus yang belum dilengkapi.',
        ]);

        BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => null,
            'is_damaged' => false,
            'borrow_date' => null,
            'expected_return_date' => null,
            'actual_return_date' => null,
            'checked_by' => null,
            'checked_at' => null,
            'check_notes' => null,
        ]);

        AuditTrail::create([
            'user_id' => $laboran,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'rejected',
            'old_values' => ['status' => 'diajukan'],
            'new_values' => ['status' => 'ditolak', 'rejection_reason' => $request->rejection_reason],
        ]);
    }

    private function pending($item, $unit, int $peminjam): void
    {
        $this->request('BR-2026-0005', [
            'requested_by' => $peminjam,
            'status' => 'diajukan',
            'purpose' => 'Pengujian sampel air untuk praktikum lingkungan',
            'requested_at' => Carbon::parse('-1 day'),
        ]);

        BorrowingItem::create([
            'borrowing_request_id' => BorrowingRequest::where('request_number', 'BR-2026-0005')->value('id'),
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => null,
            'is_damaged' => false,
            'borrow_date' => null,
            'expected_return_date' => null,
            'actual_return_date' => null,
            'checked_by' => null,
            'checked_at' => null,
            'check_notes' => null,
        ]);
    }

    private function approvedNotCheckedOut($item, $unit, int $laboran, int $peminjam): void
    {
        $request = $this->request('BR-2026-0006', [
            'requested_by' => $peminjam,
            'status' => 'disetujui',
            'purpose' => 'Pengukuran spektrofotometri sampel laboratorium',
            'requested_at' => Carbon::parse('-3 days'),
            'approved_at' => Carbon::parse('-2 days'),
            'approved_by' => $laboran,
        ]);

        BorrowingItem::create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'condition_before' => 'baik',
            'condition_after' => null,
            'is_damaged' => false,
            'borrow_date' => null,
            'expected_return_date' => null,
            'actual_return_date' => null,
            'checked_by' => null,
            'checked_at' => null,
            'check_notes' => null,
        ]);
    }
}
