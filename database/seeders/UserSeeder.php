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
        User::create([
            'name' => 'Admin Sistem',
            'email' => 'admin.sistem@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'admin_sistem',
            'phone' => '081234567890',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Laboran Utama',
            'email' => 'laboran@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'laboran',
            'phone' => '081234567891',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Kepala Laboratorium',
            'email' => 'kepala.lab@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'kepala_lab',
            'phone' => '081234567892',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Budi Santoso',
            'email' => 'peminjam@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'peminjam',
            'phone' => '081234567893',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Extra peminjam for lifecycle scenarios
        User::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'peminjam',
            'phone' => '081234567894',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Ahmad Wijaya',
            'email' => 'ahmad@wiralab.com',
            'password' => Hash::make('password'),
            'role' => 'peminjam',
            'phone' => '081234567895',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::factory(8)->peminjam()->create([
            'is_active' => true,
        ]);

        User::factory(2)->laboran()->create([
            'is_active' => true,
        ]);

        $this->command->info('Created '.User::count().' users');
    }
}
