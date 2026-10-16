<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Users
            'view-user',
            'create-user',
            'edit-user',
            'delete-user',

            // Roles
            'view-role',
            'create-role',
            'edit-role',
            'delete-role',

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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
