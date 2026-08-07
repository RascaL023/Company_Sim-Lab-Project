<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemCalibration;
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
            'item_id' => Item::factory(),
            'calibration_date' => fake()->date(),
            'next_calibration_date' => fake()->date(),
            'calibrated_by' => fake()->name(),
            'certificate_number' => fake()->uuid(),
            'result' => fake()->randomElement(['lulus', 'tidak_lulus']),
            'notes' => fake()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}
