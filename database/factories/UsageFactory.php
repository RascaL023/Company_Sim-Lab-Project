<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Usage>
 */
class UsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = Item::factory()->create();
        $isAlat = $item->isAlat();
        $status = fake()->randomElement(['dicatat', 'diverifikasi', 'ditolak']);
        $isVerified = $status === 'diverifikasi';
        $isRejected = $status === 'ditolak';
        $quantityBefore = fake()->randomFloat(2, 10, 500);
        $quantityUsed = fake()->randomFloat(2, 1, min(50, $quantityBefore));
        $quantityAfter = $quantityBefore - $quantityUsed;

        return [
            'item_id' => $item->id,
            'item_unit_id' => $isAlat && fake()->boolean(0.3) ? ItemUnit::factory()->for($item)->create()->id : null,
            'user_id' => User::factory(),
            'verified_by' => ($isVerified || $isRejected) ? User::factory() : null,
            'quantity_used' => $quantityUsed,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'usage_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => $status,
            'rejection_reason' => $isRejected ? fake()->sentence() : null,
            'verified_at' => ($isVerified || $isRejected) ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'purpose' => fake()->optional(0.7)->sentence(),
            'notes' => fake()->optional(0.5)->sentence(),
        ];
    }

    /**
     * Indicate that the usage is recorded.
     */
    public function dicatat(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dicatat',
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the usage is verified.
     */
    public function diverifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'diverifikasi',
            'verified_by' => User::factory(),
            'verified_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the usage is rejected.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ditolak',
            'verified_by' => User::factory(),
            'verified_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
