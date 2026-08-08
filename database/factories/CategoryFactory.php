<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement(['alat', 'bahan']),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the category is for bahan (materials).
     */
    public function bahan(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bahan',
        ]);
    }

    /**
     * Indicate that the category is for alat (equipment).
     */
    public function alat(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'alat',
        ]);
    }
}
