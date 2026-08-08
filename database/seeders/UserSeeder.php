<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin users
        $admin1 = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '081234567890',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $admin2 = User::create([
            'name' => 'Admin Gudang',
            'email' => 'gudang@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '081234567891',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Staff users
        $staff1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'staf',
            'phone' => '081234567892',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $staff2 = User::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'staf',
            'phone' => '081234567893',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $staff3 = User::create([
            'name' => 'Ahmad Wijaya',
            'email' => 'ahmad@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'staf',
            'phone' => '081234567894',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $staff4 = User::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'staf',
            'phone' => '081234567895',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Additional staff for testing
        User::factory(10)->create([
            'role' => 'staf',
            'is_active' => true,
        ]);

        $this->command->info('Created '.User::count().' users');
    }
}
