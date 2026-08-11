<?php

namespace Tests\Feature\Database;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Location;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Membuktikan dataset hasil DatabaseSeeder valid terhadap aturan domain.
 */
class DatabaseDataValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function seededAlat(): Collection
    {
        return Item::with('category')->get()->filter(fn (Item $item) => $item->isAlat());
    }

    private function seededBahan(): Collection
    {
        return Item::with('category')->get()->filter(fn (Item $item) => $item->isBahan());
    }

    public function test_seeded_roles_are_all_available(): void
    {
        $roles = User::query()->pluck('role');

        foreach (['admin_sistem', 'laboran', 'kepala_lab', 'peminjam'] as $role) {
            $this->assertTrue($roles->contains($role), "Role {$role} harus tersedia.");
        }
    }

    public function test_seeded_users_have_valid_roles(): void
    {
        $allowed = ['admin_sistem', 'laboran', 'kepala_lab', 'peminjam'];

        foreach (User::all() as $user) {
            $this->assertContains($user->role, $allowed);
        }
    }

    public function test_seeded_alat_items_have_no_stock_and_have_units(): void
    {
        $alat = $this->seededAlat();

        $this->assertTrue($alat->isNotEmpty(), 'Harus ada item alat pada dataset.');

        foreach ($alat as $item) {
            $this->assertNull($item->stock_quantity, "Alat {$item->code} tidak boleh punya stock_quantity.");
            $this->assertNull($item->minimum_stock, "Alat {$item->code} tidak boleh punya minimum_stock.");
            $this->assertNull($item->location_id, "Alat {$item->code} tidak boleh punya lokasi katalog.");
            $this->assertGreaterThan(0, $item->units()->count(), "Alat {$item->code} harus punya ItemUnit.");
        }
    }

    public function test_every_item_unit_has_valid_location(): void
    {
        $units = ItemUnit::with('location')->get();

        $this->assertTrue($units->isNotEmpty(), 'Dataset harus memuat ItemUnit.');

        foreach ($units as $unit) {
            $this->assertNotNull($unit->location_id, "Unit {$unit->serial_number} wajib punya lokasi.");
            $this->assertTrue($unit->location instanceof Location, "Lokasi unit {$unit->serial_number} tidak valid.");
            $this->assertTrue($unit->item->isAlat(), "Unit {$unit->serial_number} harus milik item alat.");
        }
    }

    public function test_seeded_bahan_have_non_negative_stock_and_valid_location(): void
    {
        $bahan = $this->seededBahan();

        $this->assertTrue($bahan->isNotEmpty(), 'Harus ada item bahan pada dataset.');

        foreach ($bahan as $item) {
            $this->assertGreaterThanOrEqual(0, (float) $item->stock_quantity, "Stok {$item->code} tidak boleh negatif.");
            $this->assertGreaterThanOrEqual(0, (float) $item->minimum_stock, "Minimum stok {$item->code} tidak boleh negatif.");
            $this->assertNotNull($item->location_id, "Bahan {$item->code} harus punya lokasi.");
            $this->assertTrue(Location::find($item->location_id) instanceof Location);
            $this->assertEquals(0, $item->units()->count(), "Bahan {$item->code} tidak boleh punya ItemUnit.");
        }
    }

    public function test_no_stock_movement_with_negative_after(): void
    {
        $this->assertSame(0, StockMovement::query()->where('quantity_after', '<', 0)->count());
        $this->assertSame(0, StockMovement::query()->where('quantity_before', '<', 0)->count());
    }

    public function test_stock_movement_ledger_is_mathematically_consistent(): void
    {
        $movements = StockMovement::query()
            ->orderBy('item_id')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $this->assertTrue($movements->isNotEmpty(), 'Dataset harus memuat stock movements.');

        $byItem = $movements->groupBy('item_id');

        foreach ($byItem as $itemId => $itemMovements) {
            $running = 0.0;

            foreach ($itemMovements as $m) {
                $isIncoming = Str::startsWith($m->type, 'in_');
                $expectedAfter = $isIncoming
                    ? (float) $m->quantity_before + (float) $m->quantity
                    : (float) $m->quantity_before - (float) $m->quantity;

                $this->assertEqualsWithDelta((float) $m->quantity_before, $running, 0.001,
                    "quantity_before item {$itemId} ({$m->type}) tidak sinkron dengan pergerakan sebelumnya.");
                $this->assertEqualsWithDelta($expectedAfter, (float) $m->quantity_after, 0.001,
                    "quantity_after item {$itemId} ({$m->type}) tidak konsisten.");
                $this->assertGreaterThanOrEqual(0, (float) $m->quantity_after);

                $running = (float) $m->quantity_after;
            }

            $item = Item::find($itemId);
            $this->assertEqualsWithDelta((float) $item->stock_quantity, $running, 0.001,
                "stock_quantity item {$item->code} tidak sama dengan after pergerakan terakhir.");
        }
    }

    public function test_seeded_bahan_stock_matches_last_ledger_after(): void
    {
        foreach ($this->seededBahan() as $item) {
            $last = $item->stockMovements()->orderByDesc('occurred_at')->orderByDesc('id')->first();
            $this->assertNotNull($last, "Bahan {$item->code} harus punya stock movement.");
            $this->assertEqualsWithDelta((float) $item->stock_quantity, (float) $last->quantity_after, 0.001);
        }
    }
}
