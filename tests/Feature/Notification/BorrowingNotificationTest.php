<?php

namespace Tests\Feature\Notification;

use App\Models\BorrowingRequest;
use App\Models\User;
use App\Notifications\BorrowingStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BorrowingNotificationTest extends TestCase
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

    public function test_approve_creates_notification_for_requester(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $laboran = User::factory()->laboran()->create();

        $request = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $peminjam->id,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-requests/{$request->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'disetujui');

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $peminjam->id,
            'type' => BorrowingStatusChanged::class,
        ]);

        $notification = DB::table('notifications')
            ->where('notifiable_id', $peminjam->id)
            ->where('type', BorrowingStatusChanged::class)
            ->first();

        $this->assertNotNull($notification);
        $data = json_decode($notification->data, true);
        $this->assertSame($request->id, $data['borrowing_request_id']);
        $this->assertSame('disetujui', $data['status']);
        $this->assertStringContainsString('disetujui', $data['message']);
    }

    public function test_user_cannot_see_another_users_notifications(): void
    {
        $userA = User::factory()->peminjam()->create();
        $userB = User::factory()->peminjam()->create();
        $laboran = User::factory()->laboran()->create();

        $requestA = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $userA->id,
        ]);
        $requestB = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $userB->id,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-requests/{$requestA->id}/approve")
            ->assertOk();

        Auth::forgetGuards();

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-requests/{$requestB->id}/approve")
            ->assertOk();

        Auth::forgetGuards();

        $response = $this->withToken($this->tokenFor($userA))
            ->getJson('/api/notifications')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('payload.borrowing_request_id');
        $this->assertTrue($ids->contains($requestA->id));
        $this->assertFalse($ids->contains($requestB->id));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_mark_notification_as_read_sets_read_at(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $laboran = User::factory()->laboran()->create();

        $request = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $peminjam->id,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-requests/{$request->id}/approve")
            ->assertOk();

        Auth::forgetGuards();

        $notification = $peminjam->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $this->withToken($this->tokenFor($peminjam))
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_unread_count_decreases_after_marking_read(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $laboran = User::factory()->laboran()->create();

        $request = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $peminjam->id,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-requests/{$request->id}/approve")
            ->assertOk();

        Auth::forgetGuards();

        $this->withToken($this->tokenFor($peminjam))
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        Auth::forgetGuards();

        $notification = $peminjam->notifications()->first();

        $this->withToken($this->tokenFor($peminjam))
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk();

        Auth::forgetGuards();

        $this->withToken($this->tokenFor($peminjam))
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }
}
