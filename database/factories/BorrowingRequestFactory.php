<?php

namespace Database\Factories;

use App\Models\BorrowingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BorrowingRequest>
 */
class BorrowingRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement([
            'diajukan',
            'disetujui',
            'ditolak',
            'diproses',
            'selesai',
            'batal',
        ]);

        return [
            'request_number' => 'BR-'.date('Y').fake()->unique()->numberBetween(1000, 9999),
            'requested_by' => User::factory(),
            'approved_by' => in_array($status, ['disetujui', 'ditolak', 'diproses', 'selesai'])
                ? User::factory()
                : null,
            'status' => $status,
            'purpose' => fake()->sentence(),
            'rejection_reason' => $status === 'ditolak' ? fake()->sentence() : null,
            'requested_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'approved_at' => in_array($status, ['disetujui', 'ditolak', 'diproses', 'selesai'])
                ? fake()->dateTimeBetween('-30 days', 'now')
                : null,
            'rejected_at' => $status === 'ditolak' ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the request is pending.
     */
    public function diajukan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'diajukan',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the request is approved.
     */
    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disetujui',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the request is rejected.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ditolak',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'rejected_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the request is being processed.
     */
    public function diproses(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'diproses',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the request is completed.
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
