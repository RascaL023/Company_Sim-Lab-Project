<?php

namespace Database\Factories;

use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrowing>
 */
class BorrowingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $borrowingItem = BorrowingItem::factory()->create();
        $status = fake()->randomElement(['dipinjam', 'dikembalikan', 'terlambat', 'hilang']);
        $isReturned = in_array($status, ['dikembalikan', 'hilang']);

        return [
            'borrowing_item_id' => $borrowingItem->id,
            'borrower_id' => $borrowingItem->borrowingRequest->requested_by,
            'borrow_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'expected_return_date' => fake()->dateTimeBetween('now', '+30 days'),
            'actual_return_date' => $isReturned ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'condition_before' => $borrowingItem->condition_before,
            'condition_after' => $isReturned ? fake()->randomElement(['baik', 'rusak_ringan', 'rusak_berat', 'hilang']) : null,
            'is_damaged' => $isReturned && fake()->boolean(0.2),
            'damage_notes' => fake()->optional(0.2)->sentence(),
            'checked_out_by' => User::factory(),
            'checked_in_by' => $isReturned ? User::factory() : null,
            'checked_by' => $isReturned ? User::factory() : null,
            'checked_at' => $isReturned ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'check_notes' => fake()->optional(0.3)->sentence(),
            'status' => $status,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the borrowing is active.
     */
    public function dipinjam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dipinjam',
            'actual_return_date' => null,
            'checked_in_by' => null,
            'checked_by' => null,
            'checked_at' => null,
            'condition_after' => null,
            'is_damaged' => false,
            'damage_notes' => null,
        ]);
    }

    /**
     * Indicate that the borrowing is returned.
     */
    public function dikembalikan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dikembalikan',
            'actual_return_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'checked_in_by' => User::factory(),
            'checked_by' => User::factory(),
            'checked_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'condition_after' => fake()->randomElement(['baik', 'rusak_ringan', 'rusak_berat', 'hilang']),
        ]);
    }

    /**
     * Indicate that the borrowing is overdue.
     */
    public function terlambat(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'terlambat',
            'expected_return_date' => fake()->dateTimeBetween('-60 days', '-1 day'),
            'actual_return_date' => null,
        ]);
    }
}
