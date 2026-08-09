<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand/replace users.role for the 4 use-case roles.
     *
     * Mapping data lama:
     * - admin → laboran (approve/checkout/return historis)
     * - staf  → peminjam (pengajuan peminjaman)
     *
     * MySQL: alter ENUM in two steps (widen → remap → shrink).
     * SQLite: enum is a CHECK constraint — replace column with plain string.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staf', 'admin_sistem', 'laboran', 'kepala_lab', 'peminjam') NOT NULL DEFAULT 'staf'");

            DB::table('users')->where('role', 'admin')->update(['role' => 'laboran']);
            DB::table('users')->where('role', 'staf')->update(['role' => 'peminjam']);

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin_sistem', 'laboran', 'kepala_lab', 'peminjam') NOT NULL DEFAULT 'peminjam'");

            return;
        }

        // SQLite (and others): add new column, copy mapped values, drop old CHECK column.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_new', 32)->default('peminjam');
        });

        $users = DB::table('users')->select('id', 'role')->get();
        foreach ($users as $user) {
            $mapped = match ($user->role) {
                'admin' => 'laboran',
                'staf' => 'peminjam',
                'admin_sistem', 'laboran', 'kepala_lab', 'peminjam' => $user->role,
                default => 'peminjam',
            };

            DB::table('users')->where('id', $user->id)->update(['role_new' => $mapped]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staf', 'admin_sistem', 'laboran', 'kepala_lab', 'peminjam') NOT NULL DEFAULT 'peminjam'");

            DB::table('users')->where('role', 'laboran')->update(['role' => 'admin']);
            DB::table('users')->where('role', 'admin_sistem')->update(['role' => 'admin']);
            DB::table('users')->where('role', 'kepala_lab')->update(['role' => 'admin']);
            DB::table('users')->where('role', 'peminjam')->update(['role' => 'staf']);

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staf') NOT NULL DEFAULT 'staf'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role_old', 32)->default('staf');
        });

        $users = DB::table('users')->select('id', 'role')->get();
        foreach ($users as $user) {
            $mapped = match ($user->role) {
                'laboran', 'admin_sistem', 'kepala_lab' => 'admin',
                'peminjam' => 'staf',
                'admin', 'staf' => $user->role,
                default => 'staf',
            };

            DB::table('users')->where('id', $user->id)->update(['role_old' => $mapped]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_old', 'role');
        });
    }
};
