<?php

namespace Database\Seeders;

use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $admin = $users->where('role', 'admin')->first() ?? $users->first();
        $staff = $users->where('role', 'staf')->first() ?? $users->last();

        // Get items with category eager loaded
        $items = Item::with('category')->get();

        if ($items->isEmpty()) {
            $this->command->error('No items found for stock movements');

            return;
        }

        // Create various stock movements: purchases, returns, adjustments, disposals
        $this->createPurchaseMovements($items, $admin, $staff);
        $this->createReturnMovements($items, $admin, $staff);
        $this->createAdjustmentMovements($items, $admin, $staff);
        $this->createDisposalMovements($items, $admin, $staff);
        $this->createTransferMovements($items, $admin, $staff);

        $this->command->info('Created stock movement records');
    }

    /**
     * Create purchase movements (incoming stock)
     */
    private function createPurchaseMovements($items, $admin, $staff)
    {
        $bahanItems = $items->filter(function ($item) {
            return $item->category && $item->category->type === 'bahan';
        });

        foreach ($bahanItems as $item) {
            // Create 1-3 purchase movements per item
            $purchaseCount = rand(1, 3);

            for ($i = 0; $i < $purchaseCount; $i++) {
                $quantity = fake()->randomFloat(2, 10, 100);
                $quantityBefore = $item->stock_quantity - $quantity; // Simulate what it was before
                $quantityAfter = $quantityBefore + $quantity;

                // Create stock movement
                StockMovement::create([
                    'item_id' => $item->id,
                    'item_unit_id' => null,
                    'type' => 'in_purchase',
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reference_type' => null,
                    'reference_id' => null,
                    'performed_by' => $staff->id,
                    'notes' => 'Pembelian baru dari supplier '.fake()->company(),
                    'occurred_at' => fake()->dateTimeBetween('-6 months', 'now'),
                ]);

                // Update item stock (we're simulating the accumulation)
                // Note: In reality, these would be applied in chronological order
                // For simplicity, we'll just set the final stock to what the seeder intended
                if ($i === $purchaseCount - 1) { // Last purchase for this item
                    $item->update(['stock_quantity' => $quantityAfter]);
                }

                // Create audit trail
                AuditTrail::create([
                    'user_id' => $staff->id,
                    'auditable_type' => StockMovement::class,
                    'auditable_id' => StockMovement::latest()->first()->id,
                    'action' => 'created',
                    'new_values' => StockMovement::latest()->first()->toArray(),
                ]);
            }
        }
    }

    /**
     * Create return movements (incoming from borrowing)
     */
    private function createReturnMovements($items, $admin, $staff)
    {
        // These are already created in the borrowing lifecycle seeder via the stock movement creation there
        // But we can add some additional ones if needed
        $alatItems = $items->filter(function ($item) {
            return $item->category && $item->category->type === 'alat';
        });

        foreach ($alatItems as $item) {
            // Create 0-2 return movements per item (from maintenance/calibration)
            $returnCount = rand(0, 2);

            for ($i = 0; $i < $returnCount; $i++) {
                $unit = $item->units()->inRandomOrder()->first();
                if (! $unit) {
                    continue;
                }

                $quantity = 1; // Always 1 for units
                $quantityBefore = $item->stock_quantity; // For alat, stock is conceptual
                $quantityAfter = $quantityBefore + $quantity;

                // Create stock movement
                StockMovement::create([
                    'item_id' => $item->id,
                    'item_unit_id' => $unit->id,
                    'type' => 'in_return',
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reference_type' => null,
                    'reference_id' => null,
                    'performed_by' => $staff->id,
                    'notes' => 'Pengembalian alat setelah perawatan/rutin Maintenance',
                    'occurred_at' => fake()->dateTimeBetween('-3 months', 'now'),
                ]);

                // Create audit trail
                AuditTrail::create([
                    'user_id' => $staff->id,
                    'auditable_type' => StockMovement::class,
                    'auditable_id' => StockMovement::latest()->first()->id,
                    'action' => 'created',
                    'new_values' => StockMovement::latest()->first()->toArray(),
                ]);
            }
        }
    }

    /**
     * Create adjustment movements (manual corrections)
     */
    private function createAdjustmentMovements($items, $admin, $staff)
    {
        foreach ($items as $item) {
            // Create 0-2 adjustment movements per item
            $adjustmentCount = rand(0, 2);

            for ($i = 0; $i < $adjustmentCount; $i++) {
                $adjustmentType = fake()->randomElement(['in_adjustment', 'out_adjustment']);
                $quantity = fake()->randomFloat(2, 1, 20);
                $quantityBefore = $item->stock_quantity;
                $quantityAfter = $adjustmentType === 'in_adjustment'
                    ? $quantityBefore + $quantity
                    : max(0, $quantityBefore - $quantity);

                // Create stock movement
                StockMovement::create([
                    'item_id' => $item->id,
                    'item_unit_id' => null,
                    'type' => $adjustmentType,
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reference_type' => null,
                    'reference_id' => null,
                    'performed_by' => $admin->id, // Usually admin does adjustments
                    'notes' => 'Koreksi stok berdasarkan reconciliasi fisik tanggal '.
                              fake()->dateTimeBetween('-1 month', 'now')->format('d/m/Y'),
                    'occurred_at' => fake()->dateTimeBetween('-2 months', 'now'),
                ]);

                // Update item stock
                $item->update(['stock_quantity' => $quantityAfter]);

                // Create audit trail
                AuditTrail::create([
                    'user_id' => $admin->id,
                    'auditable_type' => StockMovement::class,
                    'auditable_id' => StockMovement::latest()->first()->id,
                    'action' => 'created',
                    'new_values' => StockMovement::latest()->first()->toArray(),
                ]);
            }
        }
    }

    /**
     * Create disposal movements (expired, damaged, obsolete)
     */
    private function createDisposalMovements($items, $admin, $staff)
    {
        foreach ($items as $item) {
            // Create 0-1 disposal movements per item
            if (rand(0, 10) < 3) { // 30% chance
                $quantity = fake()->randomFloat(2, 1, $item->stock_quantity * 0.5);
                $quantityBefore = $item->stock_quantity;
                $quantityAfter = max(0, $quantityBefore - $quantity);

                // Create stock movement
                StockMovement::create([
                    'item_id' => $item->id,
                    'item_unit_id' => null,
                    'type' => 'out_disposal',
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reference_type' => null,
                    'reference_id' => null,
                    'performed_by' => $admin->id,
                    'notes' => 'Penggabalan bahan kadaluarsa/kontaminasi',
                    'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
                ]);

                // Update item stock
                $item->update(['stock_quantity' => $quantityAfter]);

                // Create audit trail
                AuditTrail::create([
                    'user_id' => $admin->id,
                    'auditable_type' => StockMovement::class,
                    'auditable_id' => StockMovement::latest()->first()->id,
                    'action' => 'created',
                    'new_values' => StockMovement::latest()->first()->toArray(),
                ]);
            }
        }
    }

    /**
     * Create transfer movements (between locations/warehouses)
     */
    private function createTransferMovements($items, $admin, $staff)
    {
        // Simple transfer simulation - just record the movement
        $items->each(function ($item) use ($staff) {
            if (rand(0, 10) < 2) { // 20% chance of transfer
                $quantity = fake()->randomFloat(2, 1, 10);
                $quantityBefore = $item->stock_quantity;
                $quantityAfter = $quantityBefore - $quantity; // Outgoing transfer

                // Create outgoing transfer
                StockMovement::create([
                    'item_id' => $item->id,
                    'item_unit_id' => null,
                    'type' => 'transfer_out',
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reference_type' => null,
                    'reference_id' => null,
                    'performed_by' => $staff->id,
                    'notes' => 'Transfer ke gudang cadangan lokasi B',
                    'occurred_at' => fake()->dateTimeBetween('-2 months', 'now'),
                ]);

                // Update item stock
                $item->update(['stock_quantity' => $quantityAfter]);

                // Create audit trail
                AuditTrail::create([
                    'user_id' => $staff->id,
                    'auditable_type' => StockMovement::class,
                    'auditable_id' => StockMovement::latest()->first()->id,
                    'action' => 'created',
                    'new_values' => StockMovement::latest()->first()->toArray(),
                ]);
            }
        });
    }
}
