<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // All permissions in the system
        $allPermissions = Permission::all();
        $allPermissionIds = $allPermissions->pluck('id')->toArray();

        // 1. Superadmin and Admin: All permissions
        $superadmin = Role::where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->permissions()->sync($allPermissionIds);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->permissions()->sync($allPermissionIds);
        }

        // 2. Manager permissions
        $managerPermissions = [
            // Users
            'view-user',
            'create-user',
            'edit-user',

            // Vendors
            'view-vendor',
            'create-vendor',
            'edit-vendor',
            'delete-vendor',

            // RO Forms
            'view-ro-form',
            'create-ro-form',
            'edit-ro-form',
            'delete-ro-form',

            // Accounts
            'view-accounts',
            'manage-accounts',

            // Domains
            'view-domain',
            'create-domain',
            'edit-domain',
            'delete-domain',

            // Payments
            'view-payment',
            'create-payment',
            'edit-payment',
            'delete-payment',

            // Purchase Invoices
            'view-purchase-invoice',
            'create-purchase-invoice',
            'edit-purchase-invoice',
            'delete-purchase-invoice',
        ];
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $managerPermissionIds = Permission::whereIn('name', $managerPermissions)->pluck('id')->toArray();
            $manager->permissions()->sync($managerPermissionIds);
        }

        // 3. Employee permissions
        $employeePermissions = [
            // Users
            'view-user',

            // Vendors
            'view-vendor',
            'create-vendor',
            'edit-vendor',

            // RO Forms
            'view-ro-form',
            'create-ro-form',
            'edit-ro-form',

            // Accounts
            'view-accounts',
            'manage-accounts',

            // Domains
            'view-domain',
            'create-domain',
            'edit-domain',

            // Payments
            'view-payment',
            'create-payment',
            'edit-payment',

            // Purchase Invoices
            'view-purchase-invoice',
            'create-purchase-invoice',
            'edit-purchase-invoice',
        ];
        $employee = Role::where('name', 'employee')->first();
        if ($employee) {
            $employeePermissionIds = Permission::whereIn('name', $employeePermissions)->pluck('id')->toArray();
            $employee->permissions()->sync($employeePermissionIds);
        }

        // 4. Accounts role permissions
        $accountsPermissions = [
            'view-accounts',
            'manage-accounts',
            'view-ro-form',
            'view-ro-revenue',
            'view-vendor',

            // Domains & Payments
            'view-domain',
            'view-payment',
            'create-payment',
            'edit-payment',

            // Purchase Invoices
            'view-purchase-invoice',
            'create-purchase-invoice',
            'edit-purchase-invoice',
        ];
        $accounts = Role::where('name', 'accounts')->first();
        if ($accounts) {
            $accountsPermissionIds = Permission::whereIn('name', $accountsPermissions)->pluck('id')->toArray();
            $accounts->permissions()->sync($accountsPermissionIds);
        }
    }
}
