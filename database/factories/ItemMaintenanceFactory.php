<?php

namespace Database\Factories;

use App\Models\ItemMaintenance;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemMaintenance>
 */
class ItemMaintenanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_unit_id' => ItemUnit::factory(),
            'maintenance_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'description' => fake()->sentence(),
            'performed_by' => fake()->name(),
            'cost' => fake()->randomFloat(2, 0, 5000),
            'status' => fake()->randomElement(['selesai', 'proses', 'tertunda']),
            'notes' => fake()->optional(0.5)->sentence(),
            'recorded_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the maintenance is completed.
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
        ]);
    }

    /**
     * Indicate that the maintenance is in progress.
     */
    public function proses(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'proses',
        ]);
    }

    /**
     * Indicate that the maintenance is pending.
     */
    public function tertunda(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'tertunda',
        ]);
    }
}
