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
            'expiry_date' => now()->addYear(),
        ]);

        $rusakAlat = Item::factory()->alat()->create(['name' => 'Sentrifus Rusak']);
        ItemUnit::factory()->for($rusakAlat)->create([
            'condition' => 'rusak_berat',
            'next_calibration_date' => now()->subDays(10),
            'expiry_date' => now()->addMonth(),
        ]);

        $expiredAlat = Item::factory()->alat()->create(['name' => 'Sensor Expired']);
        ItemUnit::factory()->for($expiredAlat)->create([
            'condition' => 'baik',
            'next_calibration_date' => now()->addMonth(),
            'expiry_date' => now()->subDays(5),
        ]);

        $bahan = Item::factory()->bahan()->create(['name' => 'Asam Klorida']);

        $condition = $this->actingAsUser($user)
            ->getJson('/api/items?condition_status=baik')
            ->assertOk();

        $conditionIds = collect($condition->json('data'))->pluck('id');
        $this->assertTrue($conditionIds->contains($baikAlat->id));
        $this->assertTrue($conditionIds->contains($expiredAlat->id));
        $this->assertFalse($conditionIds->contains($rusakAlat->id));
        $this->assertFalse($conditionIds->contains($bahan->id));

        $needsCalibration = $this->actingAsUser($user)
            ->getJson('/api/items?needs_calibration=1')
            ->assertOk();

        $needsIds = collect($needsCalibration->json('data'))->pluck('id');
        $this->assertTrue($needsIds->contains($rusakAlat->id));
        $this->assertFalse($needsIds->contains($baikAlat->id));
        $this->assertFalse($needsIds->contains($expiredAlat->id));

        $expired = $this->actingAsUser($user)
            ->getJson('/api/items?expired=1')
            ->assertOk();

        $expiredIds = collect($expired->json('data'))->pluck('id');
        $this->assertTrue($expiredIds->contains($expiredAlat->id));
        $this->assertFalse($expiredIds->contains($baikAlat->id));
        $this->assertFalse($expiredIds->contains($rusakAlat->id));

        $typeAlat = $this->actingAsUser($user)
            ->getJson('/api/items?type=alat')
            ->assertOk();

        $alatIds = collect($typeAlat->json('data'))->pluck('id');
        $this->assertTrue($alatIds->contains($baikAlat->id));
        $this->assertTrue($alatIds->contains($rusakAlat->id));
        $this->assertTrue($alatIds->contains($expiredAlat->id));
        $this->assertFalse($alatIds->contains($bahan->id));
    }
}
