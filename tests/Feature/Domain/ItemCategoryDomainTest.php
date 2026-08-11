<?php

namespace Tests\Feature\Domain;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1: Category -> Item -> ItemUnit domain.
 *
 * Sumber kebenaran tunggal untuk alat/bahan adalah Category.type.
 * Item tidak menyimpan kolom type sendiri; ItemUnit hanya untuk Item alat.
 */
class ItemCategoryDomainTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_category_is_single_source_of_truth_for_type(): void
    {
        $alat = Category::factory()->alat()->create(['name' => 'Mikroskop']);
        $bahan = Category::factory()->bahan()->create(['name' => 'Asam']);

        $itemAlat = Item::factory()->create(['category_id' => $alat->id]);
        $itemBahan = Item::factory()->create(['category_id' => $bahan->id]);

        $this->assertTrue($itemAlat->isAlat());
        $this->assertFalse($itemAlat->isBahan());
        $this->assertTrue($itemBahan->isBahan());
        $this->assertFalse($itemBahan->isAlat());
        $this->assertSame('alat', $itemAlat->type);
        $this->assertSame('bahan', $itemBahan->type);
    }

    public function test_items_table_has_no_type_column(): void
    {
        // Bukti tidak ada duplikasi `type` di Item; type diambil dari Category.
        $this->assertFalse(
            Schema::hasColumn('items', 'type'),
            'Tabel items tidak boleh punya kolom type (sumber kebenaran ada di categories.type).'
        );
    }

    public function test_item_unit_only_created_for_alat_item(): void
    {
        $laboran = User::factory()->laboran()->create();
        $itemAlat = Item::factory()->alat()->create();
        $itemBahan = Item::factory()->bahan()->create();
        $location = Location::factory()->create();

        // Sukses untuk alat.
        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $itemAlat->id,
                'serial_number' => 'CX23-001',
                'condition' => 'baik',
                'location_id' => $location->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('item_units', [
            'item_id' => $itemAlat->id,
            'serial_number' => 'CX23-001',
        ]);

        // Ditolak untuk bahan.
        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $itemBahan->id,
                'serial_number' => 'BHN-001',
                'condition' => 'baik',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.item_id.0', 'Item bahan tidak dapat memiliki unit fisik.');

        $this->assertDatabaseMissing('item_units', ['serial_number' => 'BHN-001']);
    }

    public function test_item_unit_requires_valid_location_fk(): void
    {
        $laboran = User::factory()->laboran()->create();
        $itemAlat = Item::factory()->alat()->create();

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/item-units', [
                'item_id' => $itemAlat->id,
                'serial_number' => 'CX23-002',
                'condition' => 'baik',
                'location_id' => 99999,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.location_id.0', 'Lokasi yang dipilih tidak valid.');
    }

    public function test_relation_item_has_units_and_unit_belongs_to_item(): void
    {
        $itemAlat = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($itemAlat)->create(['serial_number' => 'CX23-003']);

        $this->assertTrue($itemAlat->units()->whereKey($unit->id)->exists());
        $this->assertSame($itemAlat->id, $unit->item->id);
        $this->assertTrue($unit->item->isAlat());
    }
}
