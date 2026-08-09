<?php

namespace Tests\Feature\Borrowing;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BorrowingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(User $user): static
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'sanctum');
    }

    /**
     * @return array{0: BorrowingRequest, 1: list<BorrowingItem>}
     */
    private function approvedRequestWithItems(int $itemCount = 2): array
    {
        $staff = User::factory()->peminjam()->create();
        $request = BorrowingRequest::factory()->disetujui()->create([
            'requested_by' => $staff->id,
        ]);

        $items = [];
        for ($i = 0; $i < $itemCount; $i++) {
            $catalog = Item::factory()->alat()->create();
            $unit = ItemUnit::factory()->for($catalog)->baik()->create();
            $items[] = BorrowingItem::factory()->create([
                'borrowing_request_id' => $request->id,
                'item_id' => $catalog->id,
                'item_unit_id' => $unit->id,
                'quantity' => 1,
                'borrow_date' => null,
                'expected_return_date' => null,
                'actual_return_date' => null,
                'is_damaged' => false,
                'condition_after' => null,
                'checked_out_by' => null,
                'checked_in_by' => null,
                'checked_by' => null,
            ]);
        }

        return [$request->fresh(), $items];
    }

    private function checkoutAll(User $admin, array $items): void
    {
        foreach ($items as $item) {
            $this->actingAsUser($admin)
                ->patchJson("/api/borrowing-items/{$item->id}/checkout", [
                    'expected_return_date' => now()->addDays(3)->toIso8601String(),
                ])
                ->assertSuccessful();
        }
    }

    public function test_checkout_rejected_when_request_still_pending(): void
    {
        $admin = User::factory()->laboran()->create();
        $request = BorrowingRequest::factory()->diajukan()->create();
        $item = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'borrow_date' => null,
        ]);

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$item->id}/checkout", [
                'expected_return_date' => now()->addDays(2)->toIso8601String(),
            ])
            ->assertUnprocessable();

        $this->assertNull($item->fresh()->borrow_date);
    }

    public function test_checkout_succeeds_and_fills_borrow_fields(): void
    {
        $admin = User::factory()->laboran()->create();
        [$request, $items] = $this->approvedRequestWithItems(1);
        $item = $items[0];

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$item->id}/checkout", [
                'expected_return_date' => now()->addDays(5)->toIso8601String(),
            ])
            ->assertSuccessful();

        $fresh = $item->fresh();
        $this->assertNotNull($fresh->borrow_date);
        $this->assertSame($admin->id, $fresh->checked_out_by);
        $this->assertNotNull($fresh->expected_return_date);
        $this->assertSame('diproses', $request->fresh()->status);
    }

    public function test_parent_becomes_diproses_after_all_items_checked_out(): void
    {
        $admin = User::factory()->laboran()->create();
        [$request, $items] = $this->approvedRequestWithItems(2);

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$items[0]->id}/checkout", [
                'expected_return_date' => now()->addDays(2)->toIso8601String(),
            ])
            ->assertSuccessful();

        $this->assertSame('disetujui', $request->fresh()->status);

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$items[1]->id}/checkout", [
                'expected_return_date' => now()->addDays(2)->toIso8601String(),
            ])
            ->assertSuccessful();

        $this->assertSame('diproses', $request->fresh()->status);
    }

    public function test_return_with_damage_creates_maintenance_record(): void
    {
        $admin = User::factory()->laboran()->create();
        [$request, $items] = $this->approvedRequestWithItems(1);
        $item = $items[0];
        $this->checkoutAll($admin, $items);

        $damageNotes = 'Housing retak setelah jatuh di meja kerja.';

        $beforeCount = ItemMaintenance::where('item_unit_id', $item->item_unit_id)->count();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$item->id}/return", [
                'condition_after' => 'rusak_berat',
                'is_damaged' => true,
                'damage_notes' => $damageNotes,
                'check_notes' => 'Perlu perbaikan segera.',
            ])
            ->assertSuccessful();

        $fresh = $item->fresh();
        $this->assertNotNull($fresh->actual_return_date);
        $this->assertSame($admin->id, $fresh->checked_by);
        $this->assertNotNull($fresh->checked_at);

        $this->assertSame(
            $beforeCount + 1,
            ItemMaintenance::where('item_unit_id', $item->item_unit_id)->count()
        );

        $this->assertDatabaseHas('item_maintenances', [
            'item_unit_id' => $item->item_unit_id,
            'description' => $damageNotes,
            'status' => 'proses',
            'recorded_by' => $admin->id,
        ]);
    }

    public function test_return_without_damage_does_not_create_maintenance(): void
    {
        $admin = User::factory()->laboran()->create();
        [$request, $items] = $this->approvedRequestWithItems(1);
        $item = $items[0];
        $this->checkoutAll($admin, $items);

        $beforeCount = ItemMaintenance::count();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$item->id}/return", [
                'condition_after' => 'baik',
                'is_damaged' => false,
            ])
            ->assertSuccessful();

        $this->assertSame($beforeCount, ItemMaintenance::count());
    }

    public function test_item_cannot_be_returned_twice(): void
    {
        $admin = User::factory()->laboran()->create();
        [$request, $items] = $this->approvedRequestWithItems(1);
        $item = $items[0];
        $this->checkoutAll($admin, $items);

        $payload = [
            'condition_after' => 'baik',
            'is_damaged' => false,
        ];

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$item->id}/return", $payload)
            ->assertSuccessful();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$item->id}/return", $payload)
            ->assertUnprocessable();
    }

    public function test_parent_becomes_selesai_after_all_items_returned(): void
    {
        $admin = User::factory()->laboran()->create();
        [$request, $items] = $this->approvedRequestWithItems(2);
        $this->checkoutAll($admin, $items);

        $this->assertSame('diproses', $request->fresh()->status);

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$items[0]->id}/return", [
                'condition_after' => 'baik',
                'is_damaged' => false,
            ])
            ->assertSuccessful();

        $this->assertSame('diproses', $request->fresh()->status);

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$items[1]->id}/return", [
                'condition_after' => 'baik',
                'is_damaged' => false,
            ])
            ->assertSuccessful();

        $this->assertSame('selesai', $request->fresh()->status);
    }

    public function test_staff_cannot_return_item(): void
    {
        $admin = User::factory()->laboran()->create();
        $staff = User::factory()->peminjam()->create();
        [$request, $items] = $this->approvedRequestWithItems(1);
        $this->checkoutAll($admin, $items);

        $this->actingAsUser($staff)
            ->patchJson("/api/borrowing-items/{$items[0]->id}/return", [
                'condition_after' => 'baik',
                'is_damaged' => false,
            ])
            ->assertForbidden();

        $this->assertNull($items[0]->fresh()->actual_return_date);
    }
}
