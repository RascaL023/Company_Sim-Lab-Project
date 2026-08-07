<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'code' => fake()->unique()->bothify('??-######'),
            'name' => fake()->word(),
            'type' => fake()->randomElement(['alat', 'bahan']),
            'unit' => fake()->randomElement(['pcs', 'box', 'liter', 'gram']),
            'stock_quantity' => fake()->randomNumber(2, true),
            'minimum_stock' => fake()->randomNumber(1, true),
            'location' => fake()->word(),
            'condition_status' => fake()->randomElement(['baik', 'rusak', 'maintenance', 'kadaluarsa']),
            'manufacturer' => fake()->company(),
            'serial_number' => fake()->uuid(),
            'purchase_date' => fake()->date(),
            'expiry_date' => fake()->date(),
            'next_calibration_date' => fake()->date(),
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
