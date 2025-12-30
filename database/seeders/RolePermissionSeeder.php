<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view_dashboard',
            'manage_companies',
            'manage_cars',
            'manage_drivers',
            'manage_agreements',
            'manage_claims',
            'manage_penalties',
            'manage_expenses',
            'manage_payments',
            'manage_users',
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $superUserRole = Role::create(['name' => 'superuser']);
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);
        $driverRole = Role::create(['name' => 'driver']);

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());

    }
}
