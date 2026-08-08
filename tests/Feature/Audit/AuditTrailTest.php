<?php

namespace Tests\Feature\Audit;

use App\Models\AuditTrail;
use App\Models\BorrowingRequest;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies AuditableObserver writes trails automatically — no AuditTrail::create() in tests.
 */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_create_and_update_are_audited_automatically(): void
    {
        $staff = User::factory()->staf()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($staff);

        $request = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $staff->id,
            'purpose' => 'Pengujian rutin',
        ]);

        $createdAudit = AuditTrail::where('auditable_type', BorrowingRequest::class)
            ->where('auditable_id', $request->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($createdAudit);
        $this->assertSame($staff->id, $createdAudit->user_id);
        $this->assertIsArray($createdAudit->new_values);
        $this->assertArrayHasKey('purpose', $createdAudit->new_values);
        $this->assertSame('Pengujian rutin', $createdAudit->new_values['purpose']);

        $this->actingAs($admin);

        $request->update([
            'status' => 'disetujui',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $updatedAudit = AuditTrail::where('auditable_type', BorrowingRequest::class)
            ->where('auditable_id', $request->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($updatedAudit);
        $this->assertSame($admin->id, $updatedAudit->user_id);
        $this->assertSame('diajukan', $updatedAudit->old_values['status'] ?? null);
        $this->assertSame('disetujui', $updatedAudit->new_values['status'] ?? null);
        $this->assertArrayNotHasKey('updated_at', $updatedAudit->new_values ?? []);

        $this->actingAs($staff);

        $usage = Usage::factory()->dicatat()->create([
            'user_id' => $staff->id,
        ]);

        $this->assertDatabaseHas('audit_trails', [
            'auditable_type' => Usage::class,
            'auditable_id' => $usage->id,
            'action' => 'created',
            'user_id' => $staff->id,
        ]);

        $distinctTypes = AuditTrail::query()->distinct()->pluck('auditable_type')->all();
        $this->assertGreaterThanOrEqual(2, count($distinctTypes));
    }
}
