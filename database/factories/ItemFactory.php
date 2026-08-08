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
            'code' => fake()->unique()->bothify('ITM-######'),
            'name' => fake()->words(2, true),
            'unit' => fake()->randomElement(['pcs', 'box', 'liter', 'gram', 'kg', 'ml', 'set', 'unit']),
            'stock_quantity' => fake()->randomFloat(2, 0, 500),
            'minimum_stock' => fake()->randomFloat(2, 0, 50),
            'location' => fake()->randomElement(['Ruang Lab 1', 'Ruang Lab 2', 'Gudang A', 'Gudang B', 'Lokasi Luar']),
            'manufacturer' => fake()->optional(0.7)->company(),
            'description' => fake()->optional(0.5)->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the item is alat (equipment).
     */
    public function alat(): static
    {
        return $this->state(function (array $attributes) {
            $category = Category::factory()->alat()->create();

            return [
                'category_id' => $category->id,
                'unit' => fake()->randomElement(['pcs', 'unit', 'set']),
                'stock_quantity' => 0, // alat tracked by units
                'minimum_stock' => 0,
            ];
        });
    }

    /**
     * Indicate that the item is bahan (consumable).
     */
    public function bahan(): static
    {
        return $this->state(function (array $attributes) {
            $category = Category::factory()->bahan()->create();

            return [
                'category_id' => $category->id,
                'unit' => fake()->randomElement(['pcs', 'box', 'liter', 'gram', 'kg', 'ml']),
                'stock_quantity' => fake()->randomFloat(2, 10, 500),
                'minimum_stock' => fake()->randomFloat(2, 1, 50),
            ];
        });
    }
}
