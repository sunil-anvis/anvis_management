<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create permissions
        $p1 = Permission::firstOrCreate(['name' => 'edit users']);
        $p2 = Permission::firstOrCreate(['name' => 'delete users']);
        $p3 = Permission::firstOrCreate(['name' => 'create roles']);
        $p4 = Permission::firstOrCreate(['name' => 'edit roles']);
        $p5 = Permission::firstOrCreate(['name' => 'delete roles']);

        // create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        // Super admin doesn't strictly need mapping since hasPermission bypasses, but good practice
        $superAdminRole->permissions()->sync([$p1->id, $p2->id, $p3->id, $p4->id, $p5->id]);

        $userRole = Role::firstOrCreate(['name' => 'user']);
        // user role has no specific permissions for now

        // create a default super admin user
        User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'role_id' => $superAdminRole->id,
        ]);

        // create a default normal user
        User::firstOrCreate([
            'email' => 'user@user.com',
        ], [
            'name' => 'Normal User',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ]);
    }
}
