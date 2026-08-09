<?php

namespace Database\Seeders;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditTrailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $admin = $users->where('role', 'laboran')->first() ?? $users->first();
        $staff = $users->where('role', 'peminjam')->first() ?? $users->last();

        // Create some additional audit trails for direct user actions and bulk operations.
        $this->createUserManagementTrails($admin, $staff, $users);
        $this->createBulkOperationsTrails($staff);

        $this->command->info('Created additional audit trail records');
    }

    /**
     * Create audit trails for user management actions
     */
    private function createUserManagementTrails($admin, $staff, $users)
    {
        // Simulate user creation/update/deactivation
        $testUser = $users->where('email', 'test@example.com')->first();
        if ($testUser) {
            // User deactivation
            AuditTrail::create([
                'user_id' => $admin->id,
                'auditable_type' => 'App\\Models\\User',
                'auditable_id' => $testUser->id,
                'action' => 'updated',
                'old_values' => ['is_active' => true],
                'new_values' => ['is_active' => false],
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);

            // User reactivation
            AuditTrail::create([
                'user_id' => $admin->id,
                'auditable_type' => 'App\\Models\\User',
                'auditable_id' => $testUser->id,
                'action' => 'updated',
                'old_values' => ['is_active' => false],
                'new_values' => ['is_active' => true],
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
        }
    }

    /**
     * Create audit trails for bulk operations
     */
    private function createBulkOperationsTrails($staff)
    {
        // Simulate bulk operations like batch updates, exports, etc.
        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => 'App\\Models\\Item',
            'auditable_id' => 0, // indicates bulk operation
            'action' => 'exported',
            'new_values' => [
                'operation' => 'export_inventory',
                'format' => 'Excel',
                'records_count' => 150,
                'filters' => ['type' => 'alat', 'condition' => 'baik'],
            ],
            'ip_address' => '192.168.1.105',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        AuditTrail::create([
            'user_id' => $staff->id,
            'auditable_type' => 'App\\Models\\Usage',
            'auditable_id' => 0, // bulk operation
            'action' => 'printed',
            'new_values' => [
                'operation' => 'print_usage_report',
                'period' => 'last_month',
                'format' => 'PDF',
                'pages' => 25,
            ],
            'ip_address' => '192.168.1.105',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
    }
}
