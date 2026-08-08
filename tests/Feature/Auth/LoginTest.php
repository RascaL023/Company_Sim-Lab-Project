<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'role']]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame($user->id, PersonalAccessToken::first()->tokenable_id);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'salah',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Akun tidak aktif. Hubungi administrator.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->staf()->create([
            'email' => 'staf@example.com',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'staf@example.com')
            ->assertJsonPath('data.is_staff', true)
            ->assertJsonPath('data.is_admin', false);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'logout@example.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'bruteforce@example.com',
            'password' => 'password',
        ]);

        $payload = [
            'email' => 'bruteforce@example.com',
            'password' => 'salah',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', $payload)->assertUnprocessable();
        }

        $this->postJson('/api/login', $payload)->assertTooManyRequests();
    }
}
