<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
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
        $superAdminRole->permissions()->sync([$p1->id, $p2->id, $p3->id, $p4->id, $p5->id]);

        $userRole = Role::firstOrCreate(['name' => 'user']);

        // create default branch & department
        $defaultBranch = Branch::firstOrCreate(['name' => 'Main Branch']);
        $defaultDepartment = Department::firstOrCreate(['name' => 'Head Office']);

        // create a default super admin user
        $admin = User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'username' => 'admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'password' => Hash::make('password'),
            'role_id' => $superAdminRole->id,
            'branch_id' => $defaultBranch->id,
            'department_id' => $defaultDepartment->id,
            'status' => 'active',
        ]);

        // create a default normal user
        User::firstOrCreate([
            'email' => 'user@user.com',
        ], [
            'username' => 'normaluser',
            'first_name' => 'Normal',
            'last_name' => 'User',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
            'branch_id' => $defaultBranch->id,
            'department_id' => $defaultDepartment->id,
            'primary_lead_id' => $admin->id,
            'status' => 'active',
        ]);
    }
}
