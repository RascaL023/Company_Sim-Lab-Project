<?php

namespace Tests\Feature\Borrowing;

use App\Models\BorrowingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BorrowingStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(User $user): static
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'sanctum');
    }

    public function test_rejected_request_cannot_be_approved(): void
    {
        $admin = User::factory()->laboran()->create();
        $borrowingRequest = BorrowingRequest::factory()->ditolak()->create();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/approve")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Permintaan dengan status saat ini tidak bisa disetujui.');

        $this->assertSame('ditolak', $borrowingRequest->fresh()->status);
    }

    public function test_request_cannot_be_approved_twice(): void
    {
        $admin = User::factory()->laboran()->create();
        $borrowingRequest = BorrowingRequest::factory()->diajukan()->create();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/approve")
            ->assertSuccessful();

        $this->assertSame('disetujui', $borrowingRequest->fresh()->status);

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/approve")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Permintaan dengan status saat ini tidak bisa disetujui.');

        $this->assertSame('disetujui', $borrowingRequest->fresh()->status);
    }

    public function test_update_endpoint_cannot_change_status(): void
    {
        $staff = User::factory()->peminjam()->create();
        $borrowingRequest = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $staff->id,
            'purpose' => 'Tujuan awal',
        ]);

        $this->actingAsUser($staff)
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}", [
                'status' => 'disetujui',
                'purpose' => 'Tujuan diubah',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Field status tidak bisa diubah lewat endpoint update. Gunakan approve, reject, atau cancel.'
            );

        $fresh = $borrowingRequest->fresh();
        $this->assertSame('diajukan', $fresh->status);
        $this->assertSame('Tujuan awal', $fresh->purpose);
    }

    public function test_approved_request_cannot_be_rejected(): void
    {
        $admin = User::factory()->laboran()->create();
        $borrowingRequest = BorrowingRequest::factory()->disetujui()->create();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/reject", [
                'rejection_reason' => 'Terlambat dibatalkan.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Permintaan dengan status saat ini tidak bisa ditolak.');

        $this->assertSame('disetujui', $borrowingRequest->fresh()->status);
    }

    public function test_other_staff_cannot_cancel_request(): void
    {
        $owner = User::factory()->peminjam()->create();
        $otherStaff = User::factory()->peminjam()->create();
        $borrowingRequest = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAsUser($otherStaff)
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/cancel")
            ->assertForbidden();

        $this->assertSame('diajukan', $borrowingRequest->fresh()->status);
    }

    public function test_normal_approve_flow_sets_status_and_approver(): void
    {
        $admin = User::factory()->laboran()->create();
        $borrowingRequest = BorrowingRequest::factory()->diajukan()->create();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/approve")
            ->assertSuccessful();

        $fresh = $borrowingRequest->fresh();
        $this->assertSame('disetujui', $fresh->status);
        $this->assertSame($admin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }
}
