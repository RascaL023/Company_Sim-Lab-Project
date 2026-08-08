<?php

namespace Database\Factories;

use App\Models\ItemCalibration;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemCalibration>
 */
class ItemCalibrationFactory extends Factory
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
            'calibration_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'next_calibration_date' => fake()->optional(0.7)->dateTimeBetween('now', '+1 year'),
            'calibrated_by' => fake()->name(),
            'certificate_number' => fake()->optional(0.8)->uuid(),
            'result' => fake()->randomElement(['lulus', 'tidak_lulus']),
            'notes' => fake()->optional(0.5)->sentence(),
            'recorded_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the calibration passed.
     */
    public function lulus(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => 'lulus',
        ]);
    }

    /**
     * Indicate that the calibration failed.
     */
    public function tidakLulus(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => 'tidak_lulus',
        ]);
    }
}
