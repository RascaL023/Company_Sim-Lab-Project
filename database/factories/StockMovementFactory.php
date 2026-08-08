<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = Item::factory()->create();
        $isAlat = $item->isAlat();
        $type = fake()->randomElement([
            'in_purchase',
            'in_return',
            'in_adjustment',
            'out_borrow',
            'out_usage',
            'out_disposal',
            'out_adjustment',
            'transfer_in',
            'transfer_out',
        ]);

        $isIncoming = in_array($type, ['in_purchase', 'in_return', 'in_adjustment', 'transfer_in']);
        $quantity = fake()->randomFloat(2, 1, 100);
        $quantityBefore = fake()->randomFloat(2, 0, 500);
        $quantityAfter = $isIncoming ? $quantityBefore + $quantity : max(0, $quantityBefore - $quantity);

        return [
            'item_id' => $item->id,
            'item_unit_id' => $isAlat && fake()->boolean(0.5) ? ItemUnit::factory()->for($item)->create()->id : null,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => null,
            'reference_id' => null,
            'performed_by' => User::factory(),
            'notes' => fake()->optional(0.3)->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the movement is a purchase.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in_purchase',
        ]);
    }

    /**
     * Indicate that the movement is a return.
     */
    public function return(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in_return',
        ]);
    }

    /**
     * Indicate that the movement is a borrow out.
     */
    public function borrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out_borrow',
        ]);
    }

    /**
     * Indicate that the movement is a usage.
     */
    public function usage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out_usage',
        ]);
    }
}
