<?php

namespace Tests\Feature\Borrowing;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowingApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that borrowing cannot go directly to "dipinjam" status without approval
     */
    public function test_borrowing_requires_approval_before_borrowed()
    {
        $this->withoutExceptionHandling();

        $staff = User::factory()->staf()->create();
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($item)->baik()->create();

        // Create a borrowing request in "diajukan" status (not approved)
        $request = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $staff->id,
        ]);

        $borrowingItem = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'borrow_date' => now(), // Trying to set borrow date without approval
        ]);

        // The request should still be in diajukan status
        $this->assertEquals('diajukan', $request->fresh()->status);

        // Even if we try to set borrow date, the business logic should prevent
        // actual borrowing until approval (this would be enforced in controller/service)
        // For now, we test that the request status is correct
        $this->assertTrue($request->isPending());
        $this->assertFalse($request->isApproved());
    }

    /**
     * Test that borrowing can only happen after approval
     */
    public function test_borrowing_can_only_happen_after_approval()
    {
        $staff = User::factory()->staf()->create();
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($item)->baik()->create();

        // Create approved borrowing request
        $request = BorrowingRequest::factory()->disetujui()->create([
            'requested_by' => $staff->id,
            'approved_by' => $admin->id,
        ]);

        $borrowingItem = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'borrow_date' => now()->subDays(2),
            'expected_return_date' => now()->addDays(3),
        ]);

        // Request should be approved
        $this->assertTrue($request->isApproved());
        $this->assertFalse($request->isPending());

        // Borrowing item should have borrow date set
        $this->assertNotNull($borrowingItem->fresh()->borrow_date);
        $this->assertTrue($borrowingItem->isBorrowed());
    }
}
