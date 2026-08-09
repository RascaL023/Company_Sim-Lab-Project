<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => fake()->randomElement(['admin_sistem', 'laboran', 'kepala_lab', 'peminjam']),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function adminSistem(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin_sistem',
        ]);
    }

    public function laboran(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'laboran',
        ]);
    }

    public function kepalaLab(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'kepala_lab',
        ]);
    }

    public function peminjam(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'peminjam',
        ]);
    }
}
