<?php

namespace Tests\Feature\MasterData;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_admin_sistem_can_create_location(): void
    {
        $admin = User::factory()->adminSistem()->create();

        $this->withToken($this->tokenFor($admin))
            ->postJson('/api/locations', [
                'code' => 'A-1',
                'name' => 'Rak A Baris 1',
                'description' => 'Rak utama alat mikroskopi',
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'A-1')
            ->assertJsonPath('data.name', 'Rak A Baris 1');

        $this->assertDatabaseHas('locations', [
            'code' => 'A-1',
            'name' => 'Rak A Baris 1',
        ]);
    }

    public function test_laboran_cannot_create_location(): void
    {
        $laboran = User::factory()->laboran()->create();

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/locations', [
                'code' => 'B-1',
                'name' => 'Rak B Baris 1',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('locations', ['code' => 'B-1']);
    }

    public function test_all_roles_can_list_locations(): void
    {
        Location::factory()->create(['code' => 'C-1', 'name' => 'Rak C']);

        foreach ([
            User::factory()->adminSistem()->create(),
            User::factory()->laboran()->create(),
            User::factory()->kepalaLab()->create(),
            User::factory()->peminjam()->create(),
        ] as $user) {
            Auth::forgetGuards();

            $this->withToken($this->tokenFor($user))
                ->getJson('/api/locations')
                ->assertOk()
                ->assertJsonPath('data.0.code', 'C-1');
        }
    }

    public function test_assign_location_id_to_item_unit_appears_in_show_response(): void
    {
        $laboran = User::factory()->laboran()->create();
        $location = Location::factory()->create([
            'code' => 'D-2',
            'name' => 'Gudang D Rak 2',
        ]);
        $unit = ItemUnit::factory()->baik()->create(['location_id' => null]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/item-units/{$unit->id}", [
                'location_id' => $location->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.location_id', $location->id)
            ->assertJsonPath('data.location.code', 'D-2')
            ->assertJsonPath('data.location.name', 'Gudang D Rak 2');

        Auth::forgetGuards();

        $this->withToken($this->tokenFor($laboran))
            ->getJson("/api/item-units/{$unit->id}")
            ->assertOk()
            ->assertJsonPath('data.location_id', $location->id)
            ->assertJsonPath('data.location.name', 'Gudang D Rak 2');
    }

    public function test_legacy_location_string_migration_maps_to_location_id(): void
    {
        $migration = 'database/migrations/2026_08_09_130001_migrate_item_units_location_to_location_id.php';

        $this->artisan('migrate:rollback', [
            '--path' => $migration,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertTrue(Schema::hasColumn('item_units', 'location'));
        $this->assertFalse(Schema::hasColumn('item_units', 'location_id'));

        $item = Item::factory()->alat()->create();
        $creator = User::factory()->laboran()->create();

        $unitId = DB::table('item_units')->insertGetId([
            'item_id' => $item->id,
            'serial_number' => 'SN-LEGACY-LOC-001',
            'asset_tag' => 'AT-LEGACY-LOC-001',
            'condition' => 'baik',
            'location' => 'Ruang Mikroskopi',
            'created_by' => $creator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('migrate', [
            '--path' => $migration,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasColumn('item_units', 'location'));
        $this->assertTrue(Schema::hasColumn('item_units', 'location_id'));

        $unit = DB::table('item_units')->where('id', $unitId)->first();
        $this->assertNotNull($unit->location_id);

        $this->assertDatabaseHas('locations', [
            'id' => $unit->location_id,
            'name' => 'Ruang Mikroskopi',
        ]);
    }
}
