<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_loads(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_role_can_access_panel(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'admin', 'label' => 'Administrator']);

        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_non_admin_user_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }
}
