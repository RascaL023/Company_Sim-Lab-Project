<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemUnit>
 */
class ItemUnitFactory extends Factory
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
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'asset_tag' => fake()->unique()->bothify('AT-#####'),
            'condition' => fake()->randomElement(['baik', 'rusak_ringan', 'rusak_berat', 'hilang']),
            'location_id' => Location::factory(),
            'purchase_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'expiry_date' => fake()->optional(0.3)->dateTimeBetween('now', '+3 years'),
            'next_calibration_date' => fake()->optional(0.7)->dateTimeBetween('now', '+1 year'),
            'last_calibration_date' => fake()->optional(0.5)->dateTimeBetween('-1 year', 'now'),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the unit is in good condition.
     */
    public function baik(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'baik',
        ]);
    }

    /**
     * Indicate that the unit has light damage.
     */
    public function rusakRingan(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'rusak_ringan',
        ]);
    }

    /**
     * Indicate that the unit has heavy damage.
     */
    public function rusakBerat(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'rusak_berat',
        ]);
    }

    /**
     * Indicate that the unit is lost.
     */
    public function hilang(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'hilang',
        ]);
    }

    /**
     * Indicate that the unit needs calibration.
     */
    public function needsCalibration(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_calibration_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the unit is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
