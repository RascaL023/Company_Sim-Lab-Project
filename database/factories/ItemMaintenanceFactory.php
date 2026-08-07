<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemMaintenance;
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
            'item_id' => Item::factory(),
            'maintenance_date' => fake()->date(),
            'description' => fake()->sentence(),
            'performed_by' => fake()->name(),
            'cost' => fake()->randomFloat(2, 0, 1000),
            'status' => fake()->randomElement(['selesai', 'proses', 'tertunda']),
            'notes' => fake()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}
