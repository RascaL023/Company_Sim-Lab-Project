<?php

namespace Tests\Feature\Stock;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ItemUnitTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_laboran_can_create_item_unit(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();
        $location = Location::factory()->create(['code' => 'A-1', 'name' => 'Rak A Baris 1']);

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $item->id,
                'serial_number' => 'SN-2026-0001',
                'asset_tag' => 'AT-0001',
                'condition' => 'baik',
                'location_id' => $location->id,
                'purchase_date' => '2025-01-15',
                'notes' => 'Unit baru',
            ])
            ->assertCreated()
            ->assertJsonPath('data.serial_number', 'SN-2026-0001')
            ->assertJsonPath('data.asset_tag', 'AT-0001')
            ->assertJsonPath('data.condition', 'baik')
            ->assertJsonPath('data.location_id', $location->id)
            ->assertJsonPath('data.location.name', 'Rak A Baris 1')
            ->assertJsonPath('data.item.id', $item->id);

        $this->assertDatabaseHas('item_units', [
            'item_id' => $item->id,
            'serial_number' => 'SN-2026-0001',
            'condition' => 'baik',
            'location_id' => $location->id,
            'created_by' => $laboran->id,
        ]);
    }

    public function test_item_unit_without_calibration_or_expiry_is_valid(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();

        $response = $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $item->id,
                'serial_number' => 'SN-NO-KAL',
                'condition' => 'baik',
            ])
            ->assertCreated();

        $response
            ->assertJsonPath('data.serial_number', 'SN-NO-KAL')
            ->assertJsonPath('data.needs_calibration', false)
            ->assertJsonMissingPath('data.expiry_date')
            ->assertJsonMissingPath('data.is_expired');

        $unit = ItemUnit::where('serial_number', 'SN-NO-KAL')->first();
        $this->assertNotNull($unit);
        $this->assertNull($unit->last_calibration_date);
        $this->assertNull($unit->next_calibration_date);
        $this->assertFalse($unit->needsCalibration());
        $this->assertCount(0, $unit->calibrations);
    }

    public function test_item_units_table_has_no_expiry_column_anymore(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();

        $response = $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $item->id,
                'serial_number' => 'SN-EXPIRY-OK',
                'condition' => 'baik',
                'expiry_date' => '2027-01-01',
            ])
            ->assertCreated();

        $this->assertFalse(Schema::hasColumn('item_units', 'expiry_date'));
        $response->assertJsonMissingPath('data.expiry_date');

        $unit = ItemUnit::where('serial_number', 'SN-EXPIRY-OK')->first();
        $this->assertNotNull($unit);
        $this->assertArrayNotHasKey('expiry_date', $unit->getAttributes());
    }

    public function test_admin_sistem_can_create_item_unit(): void
    {
        $admin = User::factory()->adminSistem()->create();
        $item = Item::factory()->alat()->create();

        $this->withToken($this->tokenFor($admin))
            ->postJson('/api/item-units', [
                'item_id' => $item->id,
                'serial_number' => 'SN-ADMIN-001',
                'condition' => 'baik',
            ])
            ->assertCreated()
            ->assertJsonPath('data.serial_number', 'SN-ADMIN-001');
    }

    public function test_create_item_unit_rejected_for_bahan_item(): void
    {
        $laboran = User::factory()->laboran()->create();
        $bahan = Item::factory()->bahan()->create();

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $bahan->id,
                'serial_number' => 'SN-BAHAN-001',
                'condition' => 'baik',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Item bahan tidak dapat memiliki unit fisik.')
            ->assertJsonPath('errors.item_id.0', 'Item bahan tidak dapat memiliki unit fisik.');

        $this->assertDatabaseMissing('item_units', ['serial_number' => 'SN-BAHAN-001']);
    }

    public function test_create_item_unit_rejected_for_invalid_location(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $item->id,
                'serial_number' => 'SN-LOC-001',
                'condition' => 'baik',
                'location_id' => 99999,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.location_id.0', 'Lokasi yang dipilih tidak valid.');

        $this->assertDatabaseMissing('item_units', ['serial_number' => 'SN-LOC-001']);
    }

    public function test_create_item_unit_rejected_for_duplicate_serial_number(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();
        ItemUnit::factory()->for($item)->create(['serial_number' => 'SN-DUP-001']);

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $item->id,
                'serial_number' => 'SN-DUP-001',
                'condition' => 'baik',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.serial_number.0', 'Serial number sudah digunakan.');
    }

    public function test_peminjam_cannot_create_item_unit(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->alat()->create();

        $this->withToken($this->tokenFor($peminjam))
            ->postJson('/api/item-units', [
                'item_id' => $item->id,
                'serial_number' => 'SN-FORBID-001',
                'condition' => 'baik',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('item_units', ['serial_number' => 'SN-FORBID-001']);
    }

    public function test_kepala_lab_cannot_create_item_unit(): void
    {
        $kepalaLab = User::factory()->kepalaLab()->create();
        $item = Item::factory()->alat()->create();

        $this->withToken($this->tokenFor($kepalaLab))
            ->postJson('/api/item-units', [
                'item_id' => $item->id,
                'serial_number' => 'SN-FORBID-002',
                'condition' => 'baik',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('item_units', ['serial_number' => 'SN-FORBID-002']);
    }

    public function test_item_units_listing_includes_location_name(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();
        $location = Location::factory()->create(['code' => 'B-2', 'name' => 'Gudang B Rak 2']);
        ItemUnit::factory()->for($item)->baik()->create([
            'serial_number' => 'SN-LIST-001',
            'location_id' => $location->id,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->getJson("/api/items/{$item->id}/units")
            ->assertOk()
            ->assertJsonPath('data.0.serial_number', 'SN-LIST-001')
            ->assertJsonPath('data.0.location.name', 'Gudang B Rak 2');
    }

    public function test_available_units_filter_excludes_actively_borrowed_units(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();
        $freeUnit = ItemUnit::factory()->for($item)->baik()->create(['serial_number' => 'SN-FREE-001']);
        $busyUnit = ItemUnit::factory()->for($item)->baik()->create(['serial_number' => 'SN-BUSY-001']);

        $request = BorrowingRequest::factory()->diproses()->create();
        BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $busyUnit->id,
            'quantity' => 1,
            'borrow_date' => now()->subDay(),
            'expected_return_date' => now()->addDays(5),
            'actual_return_date' => null,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->getJson("/api/items/{$item->id}/units?available=1&per_page=100")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.serial_number', 'SN-FREE-001');
    }

    public function test_available_units_filter_excludes_lost_and_deleted_units(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();
        ItemUnit::factory()->for($item)->create(['serial_number' => 'SN-HILANG-001', 'condition' => 'hilang']);
        ItemUnit::factory()->for($item)->create(['serial_number' => 'SN-HAPUS-001', 'condition' => 'dihapus']);
        ItemUnit::factory()->for($item)->baik()->create(['serial_number' => 'SN-OK-001']);

        $this->withToken($this->tokenFor($laboran))
            ->getJson("/api/items/{$item->id}/units?available=1&per_page=100")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.serial_number', 'SN-OK-001');
    }
}
