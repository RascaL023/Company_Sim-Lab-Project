<?php

namespace Tests\Feature\Auth;

use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function bearerTokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_unauthenticated_mutating_request_is_rejected(): void
    {
        $borrowingRequest = BorrowingRequest::factory()->diajukan()->create();

        $this->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/approve")
            ->assertUnauthorized();
    }

    public function test_staff_cannot_access_admin_only_borrowing_and_usage_actions(): void
    {
        $staff = User::factory()->staf()->create();
        $borrowingRequest = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $staff->id,
        ]);
        $usage = Usage::factory()->dicatat()->create([
            'user_id' => $staff->id,
        ]);

        $this->withToken($this->bearerTokenFor($staff))
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/approve")
            ->assertForbidden();

        $this->withToken($this->bearerTokenFor($staff))
            ->patchJson("/api/borrowing-requests/{$borrowingRequest->id}/reject", [
                'rejection_reason' => 'Tidak sesuai jadwal.',
            ])
            ->assertForbidden();

        $this->withToken($this->bearerTokenFor($staff))
            ->patchJson("/api/usages/{$usage->id}/verify")
            ->assertForbidden();

        $this->withToken($this->bearerTokenFor($staff))
            ->patchJson("/api/usages/{$usage->id}/reject", [
                'rejection_reason' => 'Data tidak lengkap.',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_only_borrowing_and_usage_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staf()->create();
        $requestToApprove = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $staff->id,
        ]);
        $requestToReject = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $staff->id,
        ]);
        $usageToVerify = Usage::factory()->dicatat()->create([
            'user_id' => $staff->id,
        ]);
        $usageToReject = Usage::factory()->dicatat()->create([
            'user_id' => $staff->id,
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->patchJson("/api/borrowing-requests/{$requestToApprove->id}/approve")
            ->assertSuccessful();

        $this->withToken($this->bearerTokenFor($admin))
            ->patchJson("/api/borrowing-requests/{$requestToReject->id}/reject", [
                'rejection_reason' => 'Tidak tersedia.',
            ])
            ->assertSuccessful();

        $this->withToken($this->bearerTokenFor($admin))
            ->patchJson("/api/usages/{$usageToVerify->id}/verify")
            ->assertSuccessful();

        $this->withToken($this->bearerTokenFor($admin))
            ->patchJson("/api/usages/{$usageToReject->id}/reject", [
                'rejection_reason' => 'Data tidak lengkap.',
            ])
            ->assertSuccessful();
    }

    public function test_staff_cannot_view_or_approve_another_staffs_borrowing_request(): void
    {
        $staff = User::factory()->staf()->create();
        $otherStaff = User::factory()->staf()->create();
        $ownRequest = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $staff->id,
        ]);
        $otherRequest = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $otherStaff->id,
        ]);

        $response = $this->withToken($this->bearerTokenFor($staff))
            ->getJson('/api/borrowing-requests')
            ->assertSuccessful();

        $response->assertJsonFragment(['id' => $ownRequest->id]);
        $response->assertJsonMissing(['id' => $otherRequest->id]);

        $this->withToken($this->bearerTokenFor($staff))
            ->getJson("/api/borrowing-requests/{$otherRequest->id}")
            ->assertForbidden();

        $this->withToken($this->bearerTokenFor($staff))
            ->patchJson("/api/borrowing-requests/{$otherRequest->id}/approve")
            ->assertForbidden();
    }

    public function test_staff_store_borrowing_must_use_their_own_user_id(): void
    {
        $staff = User::factory()->staf()->create();
        $otherStaff = User::factory()->staf()->create();
        $item = Item::factory()->create();

        $this->withToken($this->bearerTokenFor($staff))
            ->postJson('/api/borrowing-requests', [
                'requested_by' => $otherStaff->id,
                'purpose' => 'Praktikum',
                'items' => [
                    ['item_id' => $item->id, 'quantity' => 1],
                ],
            ])
            ->assertForbidden();
    }
}
