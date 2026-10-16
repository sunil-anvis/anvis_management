<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $accountsRole = Role::firstOrCreate(['name' => 'accounts']);

        // Branches and departments
        $mumbaiBranch = Branch::firstOrCreate(['name' => 'mumbai']);
        $techDept = Department::firstOrCreate(['name' => 'tech']);
        $accountsDept = Department::firstOrCreate(['name' => 'accounts']);
        $paidMediaDept = Department::firstOrCreate(['name' => 'paid media']);

        // Default superadmin user
        $superadmin = User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'username' => 'admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'password' => Hash::make('password'),
            'role_id' => $superadminRole->id,
            'branch_id' => $mumbaiBranch->id,
            'department_id' => $techDept->id,
            'status' => 'active',
        ]);

        // Default manager user
        $manager = User::firstOrCreate([
            'email' => 'manager@manager.com',
        ], [
            'username' => 'manager',
            'first_name' => 'Branch',
            'last_name' => 'Manager',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'branch_id' => $mumbaiBranch->id,
            'department_id' => $techDept->id,
            'primary_lead_id' => $superadmin->id,
            'status' => 'active',
        ]);

        // Default employee user
        User::firstOrCreate([
            'email' => 'user@user.com',
        ], [
            'username' => 'employee',
            'first_name' => 'Regular',
            'last_name' => 'Employee',
            'password' => Hash::make('password'),
            'role_id' => $employeeRole->id,
            'branch_id' => $mumbaiBranch->id,
            'department_id' => $paidMediaDept->id,
            'primary_lead_id' => $manager->id,
            'status' => 'active',
        ]);

        // Default accounts user
        User::firstOrCreate([
            'email' => 'accounts@accounts.com',
        ], [
            'username' => 'accounts',
            'first_name' => 'Accounts',
            'last_name' => 'Executive',
            'password' => Hash::make('password'),
            'role_id' => $accountsRole->id,
            'branch_id' => $mumbaiBranch->id,
            'department_id' => $accountsDept->id,
            'primary_lead_id' => $manager->id,
            'status' => 'active',
        ]);
    }
}
