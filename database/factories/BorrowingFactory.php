<?php

namespace Database\Factories;

use App\Models\Borrowing;
use App\Models\Item;
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
        return [
            'item_id' => Item::factory(),
            'borrower_id' => User::factory(),
            'approved_by' => User::factory(),
            'quantity' => fake()->randomFloat(2, 1, 10),
            'borrow_date' => fake()->dateTime(),
            'expected_return_date' => fake()->dateTime(),
            'actual_return_date' => fake()->dateTime(),
            'condition_before' => fake()->word(),
            'condition_after' => fake()->word(),
            'status' => fake()->randomElement(['dipinjam', 'dikembalikan', 'terlambat']),
            'purpose' => fake()->sentence(),
            'notes' => fake()->sentence(),
        ];
    }
}
