<?php

namespace Tests\Feature\Asset;

use App\Models\AssetDisposal;
use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AssetDisposalTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    private function actingAsUser(User $user): static
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'sanctum');
    }

    public function test_laboran_can_propose_alat_disposal(): void
    {
        $laboran = User::factory()->laboran()->create();
        $alat = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($alat)->baik()->create([
            'condition' => 'rusak_berat',
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/asset-disposals', [
                'item_unit_id' => $unit->id,
                'reason' => 'rusak_total',
                'notes' => 'Tidak layak pakai',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'diusulkan')
            ->assertJsonPath('data.item_unit_id', $unit->id)
            ->assertJsonPath('data.reason', 'rusak_total');

        $this->assertDatabaseHas('asset_disposals', [
            'item_unit_id' => $unit->id,
            'status' => 'diusulkan',
            'proposed_by' => $laboran->id,
        ]);
    }

    public function test_peminjam_cannot_propose_disposal(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $bahan = Item::factory()->bahan()->create(['stock_quantity' => 10]);

        $this->withToken($this->tokenFor($peminjam))
            ->postJson('/api/asset-disposals', [
                'item_id' => $bahan->id,
                'reason' => 'kedaluwarsa',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('asset_disposals', 0);
    }

    public function test_laboran_cannot_approve_own_proposal(): void
    {
        $laboran = User::factory()->laboran()->create();
        $bahan = Item::factory()->bahan()->create(['stock_quantity' => 8]);

        $disposal = AssetDisposal::create([
            'item_id' => $bahan->id,
            'reason' => 'kedaluwarsa',
            'proposed_by' => $laboran->id,
            'proposed_at' => now(),
            'status' => 'diusulkan',
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/asset-disposals/{$disposal->id}/approve")
            ->assertForbidden();

        $this->assertDatabaseHas('asset_disposals', [
            'id' => $disposal->id,
            'status' => 'diusulkan',
        ]);
    }

    public function test_kepala_lab_can_approve_alat_disposal_and_mark_unit_dihapus(): void
    {
        $laboran = User::factory()->laboran()->create();
        $kepala = User::factory()->kepalaLab()->create();
        $alat = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($alat)->create(['condition' => 'rusak_berat']);

        $propose = $this->actingAsUser($laboran)
            ->postJson('/api/asset-disposals', [
                'item_unit_id' => $unit->id,
                'reason' => 'rusak_total',
                'notes' => 'Housing hancur',
            ])
            ->assertCreated();

        $disposalId = $propose->json('data.id');

        $this->actingAsUser($kepala)
            ->patchJson("/api/asset-disposals/{$disposalId}/approve")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'disetujui')
            ->assertJsonPath('data.reviewed_by.id', $kepala->id);

        $this->assertDatabaseHas('item_units', [
            'id' => $unit->id,
            'condition' => 'dihapus',
        ]);

        $this->assertDatabaseHas('audit_trails', [
            'auditable_type' => AssetDisposal::class,
            'auditable_id' => $disposalId,
            'action' => 'created',
            'user_id' => $laboran->id,
        ]);

        $approveAudit = AuditTrail::query()
            ->where('auditable_type', AssetDisposal::class)
            ->where('auditable_id', $disposalId)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($approveAudit);
        $this->assertSame('diusulkan', $approveAudit->old_values['status'] ?? null);
        $this->assertSame('disetujui', $approveAudit->new_values['status'] ?? null);
        $this->assertSame($kepala->id, $approveAudit->user_id);
    }

    public function test_kepala_lab_can_approve_bahan_disposal_with_out_disposal_movement(): void
    {
        $laboran = User::factory()->laboran()->create();
        $kepala = User::factory()->kepalaLab()->create();
        $bahan = Item::factory()->bahan()->create(['stock_quantity' => 25]);

        $propose = $this->actingAsUser($laboran)
            ->postJson('/api/asset-disposals', [
                'item_id' => $bahan->id,
                'reason' => 'kedaluwarsa',
                'notes' => 'Sudah lewat expiry',
            ])
            ->assertCreated();

        $disposalId = $propose->json('data.id');

        $this->actingAsUser($kepala)
            ->patchJson("/api/asset-disposals/{$disposalId}/approve")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'disetujui');

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $bahan->id,
            'type' => 'out_disposal',
            'quantity' => 25,
            'quantity_before' => 25,
            'quantity_after' => 0,
            'reference_type' => AssetDisposal::class,
            'reference_id' => $disposalId,
        ]);

        $this->assertSame(0.0, (float) $bahan->fresh()->stock_quantity);
    }

    public function test_kepala_lab_reject_requires_rejection_reason(): void
    {
        $kepala = User::factory()->kepalaLab()->create();
        $laboran = User::factory()->laboran()->create();
        $bahan = Item::factory()->bahan()->create(['stock_quantity' => 5]);

        $disposal = AssetDisposal::create([
            'item_id' => $bahan->id,
            'reason' => 'lainnya',
            'proposed_by' => $laboran->id,
            'proposed_at' => now(),
            'status' => 'diusulkan',
        ]);

        $this->withToken($this->tokenFor($kepala))
            ->patchJson("/api/asset-disposals/{$disposal->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rejection_reason']);
    }

    public function test_cannot_approve_already_approved_disposal(): void
    {
        $kepala = User::factory()->kepalaLab()->create();
        $laboran = User::factory()->laboran()->create();
        $bahan = Item::factory()->bahan()->create(['stock_quantity' => 3]);

        $disposal = AssetDisposal::create([
            'item_id' => $bahan->id,
            'reason' => 'kedaluwarsa',
            'proposed_by' => $laboran->id,
            'proposed_at' => now(),
            'status' => 'disetujui',
            'reviewed_by' => $kepala->id,
            'reviewed_at' => now(),
        ]);

        $this->withToken($this->tokenFor($kepala))
            ->patchJson("/api/asset-disposals/{$disposal->id}/approve")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Usulan dengan status saat ini tidak bisa disetujui.');
    }
}
