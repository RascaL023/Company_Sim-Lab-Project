<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Ruang Lab 1',
            'Ruang Lab 2',
            'Gudang A',
            'Gudang B',
            'Rak A Baris 1',
            'Rak B Baris 2',
        ]).' '.fake()->unique()->numerify('##');

        return [
            'code' => Location::uniqueCodeFromName($name),
            'name' => $name,
            'description' => fake()->optional(0.4)->sentence(),
        ];
    }
}
