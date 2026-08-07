<?php

namespace Database\Factories;

use App\Models\Item;
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
        return [
            'item_id' => Item::factory(),
            'user_id' => User::factory(),
            'quantity_used' => fake()->randomFloat(2, 1, 10),
            'usage_date' => fake()->dateTime(),
            'purpose' => fake()->sentence(),
            'notes' => fake()->sentence(),
        ];
    }
}
