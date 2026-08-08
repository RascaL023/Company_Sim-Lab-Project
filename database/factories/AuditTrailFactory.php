<?php

namespace Database\Factories;

use App\Models\AuditTrail;
use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use App\Models\Usage;
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
        $models = [
            Item::class => Item::factory(),
            ItemUnit::class => ItemUnit::factory(),
            BorrowingRequest::class => BorrowingRequest::factory(),
            BorrowingItem::class => BorrowingItem::factory(),
            Usage::class => Usage::factory(),
            StockMovement::class => StockMovement::factory(),
        ];
        $modelClass = fake()->randomElement(array_keys($models));
        $model = $models[$modelClass]->create();

        $action = fake()->randomElement([
            'created',
            'updated',
            'deleted',
            'approved',
            'rejected',
            'checked_out',
            'checked_in',
            'verified',
            'damaged_reported',
            'calibrated',
            'maintained',
        ]);

        return [
            'user_id' => User::factory(),
            'auditable_type' => $modelClass,
            'auditable_id' => $model->id,
            'action' => $action,
            'old_values' => $action === 'updated' ? ['field' => 'old value'] : null,
            'new_values' => $action !== 'deleted' ? ['field' => 'new value'] : null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
