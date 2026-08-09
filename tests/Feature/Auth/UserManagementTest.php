<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_non_admin_sistem_cannot_access_user_management_endpoints(): void
    {
        $laboran = User::factory()->laboran()->create();
        $target = User::factory()->peminjam()->create();
        $token = $this->tokenFor($laboran);

        $this->withToken($token)
            ->getJson('/api/users')
            ->assertForbidden();

        $this->withToken($token)
            ->postJson('/api/users', [
                'name' => 'User Baru',
                'email' => 'baru@example.com',
                'password' => 'password123',
                'role' => 'peminjam',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->getJson("/api/users/{$target->id}")
            ->assertForbidden();

        $this->withToken($token)
            ->patchJson("/api/users/{$target->id}", [
                'is_active' => false,
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/users/{$target->id}")
            ->assertForbidden();
    }

    public function test_admin_sistem_can_create_user_and_new_user_can_login(): void
    {
        $admin = User::factory()->adminSistem()->create();

        $create = $this->withToken($this->tokenFor($admin))
            ->postJson('/api/users', [
                'name' => 'Peminjam Baru',
                'email' => 'peminjam.baru@example.com',
                'password' => 'rahasia123',
                'role' => 'peminjam',
                'phone' => '08123456789',
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'peminjam.baru@example.com')
            ->assertJsonPath('data.role', 'peminjam')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('users', [
            'id' => $create->json('data.id'),
            'email' => 'peminjam.baru@example.com',
            'role' => 'peminjam',
        ]);

        $this->postJson('/api/login', [
            'email' => 'peminjam.baru@example.com',
            'password' => 'rahasia123',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'peminjam.baru@example.com')
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'role']]);
    }

    public function test_admin_sistem_can_list_and_update_users(): void
    {
        $admin = User::factory()->adminSistem()->create();
        $peminjam = User::factory()->peminjam()->create([
            'email' => 'aktif@example.com',
            'is_active' => true,
        ]);

        $this->withToken($this->tokenFor($admin))
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'email', 'role', 'is_active'],
                ],
                'links',
                'meta',
            ]);

        $this->withToken($this->tokenFor($admin))
            ->patchJson("/api/users/{$peminjam->id}", [
                'is_active' => false,
                'name' => 'Peminjam Nonaktif',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $peminjam->id)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.name', 'Peminjam Nonaktif');

        $this->assertDatabaseHas('users', [
            'id' => $peminjam->id,
            'is_active' => false,
            'name' => 'Peminjam Nonaktif',
        ]);
    }
}
