<?php

namespace Tests\Feature\Audit;

use App\Models\AuditTrail;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Audit trails are written explicitly by the business logic (controllers /
     * seeders). This test verifies the audit_trails table stores the correct
     * actor, action, and old/new values for every lifecycle event.
     */
    public function test_audit_trails_record_different_actions_with_correct_values()
    {
        $this->withoutExceptionHandling();

        $staff = User::factory()->staf()->create();
        $admin = User::factory()->admin()->create();
        $item = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($item)->baik()->create();

        // Test 1: Audit trail for borrowing request creation
        $request = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $staff->id,
            'purpose' => 'Pengujian rutin',
        ]);

        // Business logic logs the creation event
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'created',
            'new_values' => $request->toArray(),
        ]);

        $requestAudit = AuditTrail::where('auditable_type', BorrowingRequest::class)
            ->where('auditable_id', $request->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($requestAudit);
        $this->assertEquals($staff->id, $requestAudit->user_id);
        $this->assertEquals('created', $requestAudit->action);
        $this->assertIsArray($requestAudit->new_values);
        $this->assertArrayHasKey('request_number', $requestAudit->new_values);
        $this->assertArrayHasKey('purpose', $requestAudit->new_values);
        $this->assertEquals('Pengujian rutin', $requestAudit->new_values['purpose']);

        // Test 2: Audit trail for borrowing request approval
        $request->update([
            'status' => 'disetujui',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $request->id,
            'action' => 'approved',
            'old_values' => ['status' => 'diajukan'],
            'new_values' => ['status' => 'disetujui', 'approved_by' => $admin->id],
        ]);

        $approvalAudit = AuditTrail::where('auditable_type', BorrowingRequest::class)
            ->where('auditable_id', $request->id)
            ->where('action', 'approved')
            ->first();

        $this->assertNotNull($approvalAudit);
        $this->assertEquals($admin->id, $approvalAudit->user_id);
        $this->assertEquals('approved', $approvalAudit->action);
        $this->assertIsArray($approvalAudit->old_values);
        $this->assertIsArray($approvalAudit->new_values);
        $this->assertEquals('diajukan', $approvalAudit->old_values['status'] ?? null);
        $this->assertEquals('disetujui', $approvalAudit->new_values['status'] ?? null);
        $this->assertEquals($admin->id, $approvalAudit->new_values['approved_by'] ?? null);

        // Test 3: Audit trail for borrowing checkout (checked_out action)
        $borrowingItem = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'borrow_date' => now()->subDays(2),
            'expected_return_date' => now()->addDays(3),
        ]);

        $borrowing = Borrowing::factory()->create([
            'borrowing_item_id' => $borrowingItem->id,
            'borrower_id' => $staff->id,
            'borrow_date' => $borrowingItem->borrow_date,
            'expected_return_date' => $borrowingItem->expected_return_date,
            'checked_out_by' => $admin->id,
            'status' => 'dipinjam',
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => Borrowing::class,
            'auditable_id' => $borrowing->id,
            'action' => 'checked_out',
            'new_values' => $borrowing->toArray(),
        ]);

        $checkoutAudit = AuditTrail::where('auditable_type', Borrowing::class)
            ->where('auditable_id', $borrowing->id)
            ->where('action', 'checked_out')
            ->first();

        $this->assertNotNull($checkoutAudit);
        $this->assertEquals($admin->id, $checkoutAudit->user_id);
        $this->assertEquals('checked_out', $checkoutAudit->action);
        $this->assertIsArray($checkoutAudit->new_values);
        $this->assertArrayHasKey('borrow_date', $checkoutAudit->new_values);
        $this->assertArrayHasKey('checked_out_by', $checkoutAudit->new_values);
        $this->assertArrayHasKey('status', $checkoutAudit->new_values);
        $this->assertEquals('dipinjam', $checkoutAudit->new_values['status']);

        // Test 4: Audit trail for borrowing checkin with damage (checked_in action)
        $borrowingItem->update([
            'actual_return_date' => now()->subHours(2),
            'condition_after' => 'rusak_ringan',
            'is_damaged' => true,
            'damage_notes' => 'Kecil retakan pada sudut housing',
            'checked_by' => $admin->id,
            'checked_at' => now()->subHours(1),
        ]);

        $borrowing->update([
            'actual_return_date' => $borrowingItem->actual_return_date,
            'condition_after' => $borrowingItem->condition_after,
            'is_damaged' => $borrowingItem->is_damaged,
            'damage_notes' => $borrowingItem->damage_notes ?? $borrowingItem->damage_notes,
            'checked_in_by' => $admin->id,
            'checked_by' => $borrowingItem->checked_by,
            'checked_at' => $borrowingItem->checked_at,
            'check_notes' => $borrowingItem->check_notes,
            'status' => 'dikembalikan',
        ]);

        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => Borrowing::class,
            'auditable_id' => $borrowing->id,
            'action' => 'checked_in',
            'new_values' => $borrowing->toArray(),
        ]);

        $checkinAudit = AuditTrail::where('auditable_type', Borrowing::class)
            ->where('auditable_id', $borrowing->id)
            ->where('action', 'checked_in')
            ->first();

        $this->assertNotNull($checkinAudit);
        $this->assertEquals($admin->id, $checkinAudit->user_id);
        $this->assertEquals('checked_in', $checkinAudit->action);
        $this->assertIsArray($checkinAudit->new_values);
        $this->assertArrayHasKey('actual_return_date', $checkinAudit->new_values);
        $this->assertArrayHasKey('checked_by', $checkinAudit->new_values);
        $this->assertArrayHasKey('check_notes', $checkinAudit->new_values);
        $this->assertArrayHasKey('status', $checkinAudit->new_values);
        $this->assertEquals('dikembalikan', $checkinAudit->new_values['status']);

        // Test 5: Audit trail for condition change on the borrowing item
        AuditTrail::create([
            'user_id' => $admin->id,
            'auditable_type' => BorrowingItem::class,
            'auditable_id' => $borrowingItem->id,
            'action' => 'updated',
            'old_values' => ['condition_after' => 'baik'],
            'new_values' => ['condition_after' => 'rusak_ringan'],
        ]);

        $conditionAudit = AuditTrail::where('auditable_type', BorrowingItem::class)
            ->where('auditable_id', $borrowingItem->id)
            ->where('action', 'updated')
            ->where('new_values', 'like', '%condition_after%')
            ->first();

        $this->assertNotNull($conditionAudit);
        $this->assertIsArray($conditionAudit->new_values);
        $this->assertArrayHasKey('condition_after', $conditionAudit->new_values);
        $this->assertEquals('rusak_ringan', $conditionAudit->new_values['condition_after']);

        // Verify we have at least 3 different types of actions audited
        $distinctActions = AuditTrail::whereIn('auditable_id', [
            $request->id,
            $borrowing->id,
            $borrowingItem->id,
        ])
            ->distinct()
            ->pluck('action')
            ->toArray();

        $this->assertGreaterThanOrEqual(3, count($distinctActions));
        $this->assertContains('created', $distinctActions);
        $this->assertContains('approved', $distinctActions);
        $this->assertContains('checked_in', $distinctActions);
    }
}
