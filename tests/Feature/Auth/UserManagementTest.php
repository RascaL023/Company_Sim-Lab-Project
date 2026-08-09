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

    public function test_staff_cannot_access_user_management_endpoints(): void
    {
        $staff = User::factory()->staf()->create();
        $target = User::factory()->staf()->create();
        $token = $this->tokenFor($staff);

        $this->withToken($token)
            ->getJson('/api/users')
            ->assertForbidden();

        $this->withToken($token)
            ->postJson('/api/users', [
                'name' => 'User Baru',
                'email' => 'baru@example.com',
                'password' => 'password123',
                'role' => 'staf',
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

    public function test_admin_can_create_user_and_new_user_can_login(): void
    {
        $admin = User::factory()->admin()->create();

        $create = $this->withToken($this->tokenFor($admin))
            ->postJson('/api/users', [
                'name' => 'Staf Baru',
                'email' => 'staf.baru@example.com',
                'password' => 'rahasia123',
                'role' => 'staf',
                'phone' => '08123456789',
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'staf.baru@example.com')
            ->assertJsonPath('data.role', 'staf')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('users', [
            'id' => $create->json('data.id'),
            'email' => 'staf.baru@example.com',
            'role' => 'staf',
        ]);

        $this->postJson('/api/login', [
            'email' => 'staf.baru@example.com',
            'password' => 'rahasia123',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'staf.baru@example.com')
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'role']]);
    }

    public function test_admin_can_list_and_update_users(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staf()->create([
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
            ->patchJson("/api/users/{$staff->id}", [
                'is_active' => false,
                'name' => 'Staf Nonaktif',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $staff->id)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.name', 'Staf Nonaktif');

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'is_active' => false,
            'name' => 'Staf Nonaktif',
        ]);
    }
}
