<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function authenticateUser(User $user): string
    {
        $token = Str::random(60);
        UserToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'ip_address' => '127.0.0.1',
            'expiry_time' => Carbon::now()->addHours(24),
            'is_revoked' => false,
        ]);
        return $token;
    }

    public function test_user_without_permission_cannot_list_users(): void
    {
        $role = Role::create(['name' => 'employee']);
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_user_with_permission_only_sees_their_department_users(): void
    {
        $viewUserPerm = Permission::create(['name' => 'view-user']);
        $role = Role::create(['name' => 'manager']);
        $role->permissions()->sync([$viewUserPerm->id]);

        $dept1 = Department::create(['name' => 'Tech']);
        $dept2 = Department::create(['name' => 'Marketing']);

        $user1 = User::create([
            'username' => 'tech_manager',
            'email' => 'tech@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'department_id' => $dept1->id,
        ]);

        $user2 = User::create([
            'username' => 'tech_employee',
            'email' => 'tech_emp@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'department_id' => $dept1->id,
        ]);

        $user3 = User::create([
            'username' => 'marketing_employee',
            'email' => 'marketing@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'department_id' => $dept2->id,
        ]);

        $token = $this->authenticateUser($user1);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $response->assertStatus(200);
        $data = $response->json('data');

        $usernames = collect($data)->pluck('username')->all();
        $this->assertContains('tech_manager', $usernames);
        $this->assertContains('tech_employee', $usernames);
        $this->assertNotContains('marketing_employee', $usernames);
    }

    public function test_admin_and_superadmin_can_see_all_users(): void
    {
        $superadminRole = Role::create(['name' => 'superadmin']);
        $managerRole = Role::create(['name' => 'manager']);

        $dept1 = Department::create(['name' => 'Tech']);
        $dept2 = Department::create(['name' => 'Marketing']);

        $admin = User::create([
            'username' => 'superadmin_user',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $superadminRole->id,
            'department_id' => $dept1->id,
        ]);

        User::create([
            'username' => 'tech_user',
            'email' => 'tech@example.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'department_id' => $dept1->id,
        ]);

        User::create([
            'username' => 'marketing_user',
            'email' => 'marketing@example.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'department_id' => $dept2->id,
        ]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $response->assertStatus(200);
        $data = $response->json('data');

        $usernames = collect($data)->pluck('username')->all();
        $this->assertContains('superadmin_user', $usernames);
        $this->assertContains('tech_user', $usernames);
        $this->assertContains('marketing_user', $usernames);
    }

    public function test_user_cannot_view_user_outside_department_in_show(): void
    {
        $viewUserPerm = Permission::create(['name' => 'view-user']);
        $role = Role::create(['name' => 'manager']);
        $role->permissions()->sync([$viewUserPerm->id]);

        $dept1 = Department::create(['name' => 'Tech']);
        $dept2 = Department::create(['name' => 'Marketing']);

        $user1 = User::create([
            'username' => 'tech_manager',
            'email' => 'tech@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'department_id' => $dept1->id,
        ]);

        $user2 = User::create([
            'username' => 'marketing_employee',
            'email' => 'marketing@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'department_id' => $dept2->id,
        ]);

        $token = $this->authenticateUser($user1);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users/' . $user2->id);

        $response->assertStatus(403);
    }
}
