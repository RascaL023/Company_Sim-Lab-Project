<?php

namespace Database\Factories;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BorrowingItem>
 */
class BorrowingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $borrowingRequest = BorrowingRequest::factory()->create();
        $item = Item::factory()->create();
        $isAlat = $item->isAlat();

        $status = $borrowingRequest->status;
        $hasDates = in_array($status, ['disetujui', 'diproses', 'selesai']);

        return [
            'borrowing_request_id' => $borrowingRequest->id,
            'item_id' => $item->id,
            'item_unit_id' => $isAlat ? ItemUnit::factory()->for($item)->create()->id : null,
            'quantity' => $isAlat ? 1 : fake()->randomFloat(2, 1, 10),
            'condition_before' => fake()->randomElement(['baik', 'rusak_ringan', 'rusak_berat', 'hilang']),
            'condition_after' => in_array($status, ['selesai'])
                ? fake()->randomElement(['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])
                : null,
            'is_damaged' => in_array($status, ['selesai']) && fake()->boolean(0.2),
            'damage_notes' => fake()->optional(0.2)->sentence(),
            'borrow_date' => $hasDates ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'expected_return_date' => $hasDates ? fake()->dateTimeBetween('now', '+30 days') : null,
            'actual_return_date' => $status === 'selesai' ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'checked_by' => $status === 'selesai' ? User::factory() : null,
            'checked_at' => $status === 'selesai' ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'check_notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the item is for alat (equipment).
     */
    public function alat(): static
    {
        return $this->state(function (array $attributes) {
            $item = Item::factory()->alat()->create();

            return [
                'item_id' => $item->id,
                'item_unit_id' => ItemUnit::factory()->for($item)->create()->id,
                'quantity' => 1,
            ];
        });
    }

    /**
     * Indicate that the item is for bahan (consumable).
     */
    public function bahan(): static
    {
        return $this->state(function (array $attributes) {
            $item = Item::factory()->bahan()->create();

            return [
                'item_id' => $item->id,
                'item_unit_id' => null,
                'quantity' => fake()->randomFloat(2, 1, 50),
            ];
        });
    }
}
