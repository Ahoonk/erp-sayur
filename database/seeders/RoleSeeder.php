<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',

            // Profile Management
            'view own profile',
            'edit own profile',
            'view any profile',

            // Role & Permission Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'assign permissions',

            // Master Data
            'view master-data',
            'create master-data',
            'edit master-data',
            'delete master-data',

            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Purchase (Pembelian)
            'view purchases',
            'create purchases',
            'edit purchases',
            'delete purchases',

            // Sales (Penjualan)
            'view sales',
            'create sales',
            'edit sales',
            'delete sales',

            // Service (Service HP)
            'view services',
            'create services',
            'edit services',
            'delete services',

            // Reports
            'view reports',
            'view all reports',

            // Barcode
            'print barcode',

            // General
            'access dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // === Super Admin - Full Access ===
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // === Admin - Everything except roles/permissions management ===
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $adminPermissions = [
            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
            // Profile
            'view own profile',
            'edit own profile',
            'view any profile',
            // Master Data
            'view master-data',
            'create master-data',
            'edit master-data',
            'delete master-data',
            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',
            // Purchase
            'view purchases',
            'create purchases',
            'edit purchases',
            'delete purchases',
            // Sales
            'view sales',
            'create sales',
            'edit sales',
            'delete sales',
            // Service
            'view services',
            'create services',
            'edit services',
            'delete services',
            // Reports
            'view reports',
            'view all reports',
            // Barcode
            'print barcode',
            // General
            'access dashboard',
        ];

        $admin->givePermissionTo($adminPermissions);

        // === Owner - Same as Admin ===
        $owner = Role::firstOrCreate(['name' => 'owner']);
        $owner->givePermissionTo($adminPermissions);

        // === Kasir - Limited to daily operations ===
        $kasir = Role::firstOrCreate(['name' => 'kasir']);
        $kasir->givePermissionTo([
            // Profile
            'view own profile',
            'edit own profile',
            // Master data (read-only for stock forms)
            'view master-data',
            'create master-data',
            'edit master-data',
            // Products
            'view products',
            // Purchases (view, create, edit - no delete)
            'view purchases',
            'create purchases',
            'edit purchases',
            // Sales (create & view)
            'view sales',
            'create sales',
            // Service (create & view)
            'view services',
            'create services',
            // Reports (own only)
            'view reports',
            // Barcode
            'print barcode',
            // General
            'access dashboard',
        ]);

        $this->command->info('Roles and Permissions created successfully!');
        $this->command->info('');
        $this->command->info('=== Role Capabilities ===');
        $this->command->info('');
        $this->command->info('SUPER ADMIN:');
        $this->command->info('  ✓ Full system access');
        $this->command->info('');
        $this->command->info('ADMIN:');
        $this->command->info('  ✓ Manage users, master data, products');
        $this->command->info('  ✓ Manage purchases, sales, services');
        $this->command->info('  ✓ View all reports');
        $this->command->info('  ✗ Cannot manage roles/permissions');
        $this->command->info('');
        $this->command->info('KASIR:');
        $this->command->info('  ✓ Create sales & services');
        $this->command->info('  ✓ View products & print barcode');
        $this->command->info('  ✓ View own reports');
        $this->command->info('  ✗ Cannot manage master data or users');
    }
}
