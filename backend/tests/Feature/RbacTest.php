<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbac_tables_exist(): void
    {
        foreach (['roles', 'permissions', 'permission_role', 'role_user'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing $table table");
        }
    }

    public function test_user_can_be_assigned_role_and_checked_for_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'admin', 'label' => 'Administrator']);
        $permission = Permission::query()->create(['name' => 'users.manage', 'label' => 'Manage users']);

        $role->permissions()->attach($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasPermissionTo('users.manage'));
        $this->assertFalse($user->hasPermissionTo('events.publish'));
    }
}
