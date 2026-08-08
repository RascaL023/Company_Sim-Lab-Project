<?php

namespace Tests\Feature\Audit;

use App\Models\AuditTrail;
use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AutomaticAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(User $user): static
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'sanctum');
    }

    public function test_http_lifecycle_writes_automatic_audit_trails(): void
    {
        $staff = User::factory()->staf()->create();
        $admin = User::factory()->admin()->create();
        $alat = Item::factory()->alat()->create();
        $bahan = Item::factory()->bahan()->create(['stock_quantity' => 100]);

        $createResponse = $this->actingAsUser($staff)
            ->postJson('/api/borrowing-requests', [
                'requested_by' => $staff->id,
                'purpose' => 'Audit otomatis',
                'items' => [
                    ['item_id' => $alat->id, 'quantity' => 1],
                ],
            ])
            ->assertCreated();

        $borrowingRequestId = $createResponse->json('data.id') ?? $createResponse->json('id');

        $this->assertDatabaseHas('audit_trails', [
            'action' => 'created',
            'auditable_type' => BorrowingRequest::class,
            'auditable_id' => $borrowingRequestId,
            'user_id' => $staff->id,
        ]);

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-requests/{$borrowingRequestId}/approve")
            ->assertSuccessful();

        $approvalAudit = AuditTrail::where('auditable_type', BorrowingRequest::class)
            ->where('auditable_id', $borrowingRequestId)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($approvalAudit);
        $this->assertSame('diajukan', $approvalAudit->old_values['status'] ?? null);
        $this->assertSame('disetujui', $approvalAudit->new_values['status'] ?? null);
        $this->assertSame($admin->id, $approvalAudit->user_id);

        $usageResponse = $this->actingAsUser($staff)
            ->postJson('/api/usages', [
                'item_id' => $bahan->id,
                'quantity_used' => 5,
                'purpose' => 'Pemakaian untuk audit',
            ])
            ->assertCreated();

        $usageId = $usageResponse->json('data.id') ?? $usageResponse->json('id');

        $this->actingAsUser($admin)
            ->patchJson("/api/usages/{$usageId}/verify")
            ->assertSuccessful();

        $usageVerifyAudit = AuditTrail::where('auditable_type', Usage::class)
            ->where('auditable_id', $usageId)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($usageVerifyAudit);
        $this->assertSame('dicatat', $usageVerifyAudit->old_values['status'] ?? null);
        $this->assertSame('diverifikasi', $usageVerifyAudit->new_values['status'] ?? null);
        $this->assertSame($admin->id, $usageVerifyAudit->user_id);

        $auditableTypes = AuditTrail::query()
            ->distinct()
            ->pluck('auditable_type')
            ->all();

        $this->assertGreaterThanOrEqual(3, count($auditableTypes));
        $this->assertContains(BorrowingRequest::class, $auditableTypes);
        $this->assertContains(Usage::class, $auditableTypes);

        $hasThirdType = collect($auditableTypes)
            ->contains(fn (string $type) => in_array($type, [
                BorrowingItem::class,
                StockMovement::class,
            ], true));

        $this->assertTrue(
            $hasThirdType,
            'Expected a third auditable type (BorrowingItem or StockMovement) from the full scenario.'
        );
    }
}
