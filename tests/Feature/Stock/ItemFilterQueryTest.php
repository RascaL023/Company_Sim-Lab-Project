<?php

namespace Tests\Feature\Stock;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ItemFilterQueryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(User $user): static
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'sanctum');
    }

    public function test_item_filters_query_units_and_categories_without_sql_errors(): void
    {
        $user = User::factory()->laboran()->create();

        $baikAlat = Item::factory()->alat()->create(['name' => 'Mikroskop Baik']);
        ItemUnit::factory()->for($baikAlat)->create([
            'condition' => 'baik',
            'next_calibration_date' => now()->addMonths(3),
        ]);

        $rusakAlat = Item::factory()->alat()->create(['name' => 'Sentrifus Rusak']);
        ItemUnit::factory()->for($rusakAlat)->create([
            'condition' => 'rusak_berat',
            'next_calibration_date' => now()->subDays(10),
        ]);

        $stabilAlat = Item::factory()->alat()->create(['name' => 'Sensor Stabil']);
        ItemUnit::factory()->for($stabilAlat)->create([
            'condition' => 'baik',
            'next_calibration_date' => now()->addMonth(),
        ]);

        $unitTanpaJadwal = Item::factory()->alat()->create(['name' => 'Alat Tanpa Kalibrasi']);
        ItemUnit::factory()->for($unitTanpaJadwal)->baik()->create([
            'serial_number' => 'SN-TANPA-KAL',
        ]);

        $bahan = Item::factory()->bahan()->create(['name' => 'Asam Klorida']);

        $condition = $this->actingAsUser($user)
            ->getJson('/api/items?condition_status=baik')
            ->assertOk();

        $conditionIds = collect($condition->json('data'))->pluck('id');
        $this->assertTrue($conditionIds->contains($baikAlat->id));
        $this->assertTrue($conditionIds->contains($stabilAlat->id));
        $this->assertTrue($conditionIds->contains($unitTanpaJadwal->id));
        $this->assertFalse($conditionIds->contains($rusakAlat->id));
        $this->assertFalse($conditionIds->contains($bahan->id));

        $needsCalibration = $this->actingAsUser($user)
            ->getJson('/api/items?needs_calibration=1')
            ->assertOk();

        $needsIds = collect($needsCalibration->json('data'))->pluck('id');
        $this->assertTrue($needsIds->contains($rusakAlat->id));
        $this->assertFalse($needsIds->contains($baikAlat->id));
        $this->assertFalse($needsIds->contains($stabilAlat->id));
        $this->assertFalse($needsIds->contains($unitTanpaJadwal->id));

        $typeAlat = $this->actingAsUser($user)
            ->getJson('/api/items?type=alat')
            ->assertOk();

        $alatIds = collect($typeAlat->json('data'))->pluck('id');
        $this->assertTrue($alatIds->contains($baikAlat->id));
        $this->assertTrue($alatIds->contains($rusakAlat->id));
        $this->assertTrue($alatIds->contains($stabilAlat->id));
        $this->assertTrue($alatIds->contains($unitTanpaJadwal->id));
        $this->assertFalse($alatIds->contains($bahan->id));
    }
}
