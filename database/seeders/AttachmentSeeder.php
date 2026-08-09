<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\BorrowingItem;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Models\ItemUnit;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttachmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $admin = $users->where('role', 'laboran')->first() ?? $users->first();
        $staff = $users->where('role', 'peminjam')->first() ?? $users->last();

        // Create attachments for various models
        $this->createItemAttachments($admin, $staff);
        $this->createBorrowingAttachments($admin, $staff);
        $this->createUsageAttachments($admin, $staff);
        $this->createMaintenanceAttachments($admin, $staff);

        $this->command->info('Created attachment records');
    }

    /**
     * Create attachments for items and units (photos, documents, etc.)
     */
    private function createItemAttachments($admin, $staff)
    {
        $items = Item::all();

        foreach ($items as $item) {
            // Create 0-3 attachments per item
            $attachmentCount = rand(0, 3);

            for ($i = 0; $i < $attachmentCount; $i++) {
                $attachmentTypes = ['datasheet', 'manual', 'warranty', 'photo', 'certificate'];
                $type = $attachmentTypes[array_rand($attachmentTypes)];

                $ext = ($type === 'photo') ? 'jpg' : (($type === 'manual' || $type === 'datasheet') ? 'pdf' : 'png');
                $mime = ($type === 'photo') ? 'image/jpeg' : (($type === 'manual' || $type === 'datasheet') ? 'application/pdf' : 'image/png');

                Attachment::create([
                    'attachable_type' => Item::class,
                    'attachable_id' => $item->id,
                    'type' => $type,
                    'file_path' => 'items/'.$item->code.'/'.$type.'_'.fake()->uuid().'.'.$ext,
                    'original_filename' => $item->name.' - '.ucfirst($type).'.'.$ext,
                    'mime_type' => $mime,
                    'file_size' => ($type === 'photo') ? rand(500000, 5000000) : (($type === 'manual' || $type === 'datasheet') ? rand(100000, 2000000) : rand(50000, 500000)),
                    'disk' => 'public',
                    'description' => 'Dokumentasi '.$type.' untuk '.$item->name,
                    'uploaded_by' => (rand(0, 1) ? $admin->id : $staff->id),
                ]);
            }
        }

        // Create attachments for item units (condition photos, calibration certs, etc.)
        $itemUnits = ItemUnit::all();

        foreach ($itemUnits as $unit) {
            // Create 0-2 attachments per unit
            $attachmentCount = rand(0, 2);

            for ($i = 0; $i < $attachmentCount; $i++) {
                $attachmentTypes = ['condition_before', 'condition_after', 'calibration_certificate',
                    'maintenance_photo', 'damage_evidence'];
                $type = $attachmentTypes[array_rand($attachmentTypes)];

                $ext = (($type === 'condition_before' || $type === 'condition_after' ||
                        $type === 'maintenance_photo' || $type === 'damage_evidence')) ? 'jpg' : 'pdf';
                $mime = (($type === 'condition_before' || $type === 'condition_after' ||
                         $type === 'maintenance_photo' || $type === 'damage_evidence')) ? 'image/jpeg' : 'application/pdf';

                Attachment::create([
                    'attachable_type' => ItemUnit::class,
                    'attachable_id' => $unit->id,
                    'type' => $type,
                    'file_path' => 'item_units/'.$unit->serial_number.'/'.$type.'_'.fake()->uuid().'.'.$ext,
                    'original_filename' => 'Unit_'.$unit->serial_number.'_'.ucfirst($type).'.'.$ext,
                    'mime_type' => $mime,
                    'file_size' => ((($type === 'condition_before' || $type === 'condition_after' ||
                                    $type === 'maintenance_photo' || $type === 'damage_evidence')) ? rand(300000, 3000000) : rand(100000, 1000000)),
                    'disk' => 'public',
                    'description' => 'Foto kondisi unit '.$unit->serial_number.' - '.ucfirst($type),
                    'uploaded_by' => (rand(0, 1) ? $admin->id : $staff->id),
                ]);
            }
        }
    }

    /**
     * Create attachments for borrowing transactions (condition evidence, receipts)
     */
    private function createBorrowingAttachments($admin, $staff)
    {
        $borrowingItems = BorrowingItem::all();

        foreach ($borrowingItems as $borrowingItem) {
            // Create attachments for items that were borrowed
            if ($borrowingItem->borrow_date) {
                // Condition before attachment
                Attachment::create([
                    'attachable_type' => BorrowingItem::class,
                    'attachable_id' => $borrowingItem->id,
                    'type' => 'condition_before',
                    'file_path' => 'borrowings/'.$borrowingItem->id.'/condition_before_'.fake()->uuid().'.jpg',
                    'original_filename' => 'condition_before.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => rand(200000, 1500000),
                    'disk' => 'public',
                    'description' => 'Foto kondisi barang sebelum peminjaman',
                    'uploaded_by' => ($borrowingItem->checked_by ?: $staff->id),
                ]);

                // Condition after attachment (if returned)
                if ($borrowingItem->actual_return_date) {
                    Attachment::create([
                        'attachable_type' => BorrowingItem::class,
                        'attachable_id' => $borrowingItem->id,
                        'type' => 'condition_after',
                        'file_path' => 'borrowings/'.$borrowingItem->id.'/condition_after_'.fake()->uuid().'.jpg',
                        'original_filename' => 'condition_after.jpg',
                        'mime_type' => 'image/jpeg',
                        'file_size' => rand(200000, 1500000),
                        'disk' => 'public',
                        'description' => 'Foto kondisi barang setelah peminjaman',
                        'uploaded_by' => ($borrowingItem->checked_by ?: $admin->id),
                    ]);
                }

                // Receipt/proof of borrowing
                Attachment::create([
                    'attachable_type' => BorrowingItem::class,
                    'attachable_id' => $borrowingItem->id,
                    'type' => 'borrow_receipt',
                    'file_path' => 'borrowings/'.$borrowingItem->id.'/receipt_'.fake()->uuid().'.pdf',
                    'original_filename' => 'peminjaman_receipt.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => rand(50000, 300000),
                    'disk' => 'public',
                    'description' => 'Kuittansi peminjaman alat',
                    'uploaded_by' => $borrowingItem->borrowingRequest->requested_by,
                ]);
            }
        }
    }

    /**
     * Create attachments for usage records
     */
    private function createUsageAttachments($admin, $staff)
    {
        $usages = Usage::where('status', 'diverifikasi')->get();

        foreach ($usages as $usage) {
            // Create attachment for verified usages (lab results, etc.)
            Attachment::create([
                'attachable_type' => Usage::class,
                'attachable_id' => $usage->id,
                'type' => 'usage_record',
                'file_path' => 'usages/'.$usage->id.'/record_'.fake()->uuid().'.pdf',
                'original_filename' => 'rekord_penggunaan.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => rand(100000, 800000),
                'disk' => 'public',
                'description' => 'Rekord penggunaan bahan dengan hasil analisis',
                'uploaded_by' => ($usage->verified_by ?: $staff->id),
            ]);
        }
    }

    /**
     * Create attachments for maintenance records
     */
    private function createMaintenanceAttachments($admin, $staff)
    {
        $maintenances = ItemMaintenance::all();

        foreach ($maintenances as $maintenance) {
            // Create attachment for completed maintenances
            if ($maintenance->status === 'selesai') {
                Attachment::create([
                    'attachable_type' => ItemMaintenance::class,
                    'attachable_id' => $maintenance->id,
                    'type' => 'maintenance_photo',
                    'file_path' => 'maintenances/'.$maintenance->id.'/photo_'.fake()->uuid().'.jpg',
                    'original_filename' => 'maintenance_photo.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => rand(400000, 2500000),
                    'disk' => 'public',
                    'description' => 'Foto dokumentasi maintenance',
                    'uploaded_by' => $maintenance->recorded_by,
                ]);
            }
        }
    }
}
