<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Satu user per role utama (admin_sistem, laboran, kepala_lab, peminjam).
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Admin Sistem', 'email' => 'admin.sistem@wiralab.com', 'role' => 'admin_sistem', 'phone' => '081234567890'],
            ['name' => 'Laboran Utama', 'email' => 'laboran@wiralab.com', 'role' => 'laboran', 'phone' => '081234567891'],
            ['name' => 'Kepala Laboratorium', 'email' => 'kepala.lab@wiralab.com', 'role' => 'kepala_lab', 'phone' => '081234567892'],
            ['name' => 'Budi Santoso', 'email' => 'peminjam@wiralab.com', 'role' => 'peminjam', 'phone' => '081234567893'],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password'),
                'role' => $user['role'],
                'phone' => $user['phone'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('Created '.User::count().' users');
    }
}
