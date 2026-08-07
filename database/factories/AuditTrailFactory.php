<?php

namespace Database\Factories;

use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditTrail>
 */
class AuditTrailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'auditable_type' => Item::class,
            'auditable_id' => Item::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'deleted']),
            'old_values' => ['field' => 'old value'],
            'new_values' => ['field' => 'new value'],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
