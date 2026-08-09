<?php

namespace Tests\Feature\Auth;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_peminjam_cannot_approve_borrowing_request(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $request = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $peminjam->id,
        ]);

        $this->withToken($this->tokenFor($peminjam))
            ->patchJson("/api/borrowing-requests/{$request->id}/approve")
            ->assertForbidden();
    }

    public function test_laboran_can_approve_borrowing_request(): void
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $request = BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $peminjam->id,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-requests/{$request->id}/approve")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'disetujui');

        $this->assertDatabaseHas('borrowing_requests', [
            'id' => $request->id,
            'status' => 'disetujui',
            'approved_by' => $laboran->id,
        ]);
    }

    public function test_laboran_cannot_access_user_management(): void
    {
        $laboran = User::factory()->laboran()->create();

        $this->withToken($this->tokenFor($laboran))
            ->getJson('/api/users')
            ->assertForbidden();

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/users', [
                'name' => 'Hacker',
                'email' => 'hacker@example.com',
                'password' => 'password123',
                'role' => 'peminjam',
            ])
            ->assertForbidden();
    }

    public function test_admin_sistem_can_create_user(): void
    {
        $admin = User::factory()->adminSistem()->create();

        $this->withToken($this->tokenFor($admin))
            ->postJson('/api/users', [
                'name' => 'User Matrix',
                'email' => 'matrix@example.com',
                'password' => 'password123',
                'role' => 'laboran',
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'matrix@example.com')
            ->assertJsonPath('data.role', 'laboran')
            ->assertJsonPath('data.is_laboran', true);
    }

    public function test_kepala_lab_cannot_checkout_borrowing_item(): void
    {
        $kepala = User::factory()->kepalaLab()->create();
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->alat()->create();

        $request = BorrowingRequest::factory()->disetujui()->create([
            'requested_by' => $peminjam->id,
            'approved_by' => $laboran->id,
        ]);

        $borrowingItem = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'borrow_date' => null,
            'actual_return_date' => null,
        ]);

        $this->withToken($this->tokenFor($kepala))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_peminjam_can_view_item_catalog(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        Item::factory()->count(2)->create();

        $this->withToken($this->tokenFor($peminjam))
            ->getJson('/api/items')
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'code'],
                ],
            ]);
    }
}
