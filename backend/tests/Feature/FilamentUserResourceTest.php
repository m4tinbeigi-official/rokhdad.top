<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_management_pages(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/users/create')->assertOk();
        $this->actingAs($admin)->get("/admin/users/{$user->id}/edit")->assertOk();
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = $this->adminUser();
        $managerRole = Role::query()->create(['name' => 'manager', 'label' => 'Manager']);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Managed User',
                'email' => 'managed@example.com',
                'phone_e164' => '+989121234567',
                'password' => 'password123',
                'status' => 'active',
                'locale' => 'fa',
                'timezone' => 'Asia/Tehran',
                'roles' => [$managerRole->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'managed@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertTrue($user->hasRole('manager'));
    }

    public function test_admin_can_update_user_without_overwriting_password(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create([
            'email' => 'before@example.com',
            'password' => Hash::make('original-password'),
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'Updated User',
                'email' => 'after@example.com',
                'phone_e164' => $user->phone_e164,
                'password' => null,
                'status' => 'disabled',
                'locale' => 'fa',
                'timezone' => 'Asia/Tehran',
                'roles' => [],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('after@example.com', $user->email);
        $this->assertSame('disabled', $user->status);
        $this->assertTrue(Hash::check('original-password', $user->password));
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->create(['name' => 'admin', 'label' => 'Administrator']);
        $admin->assignRole($role);

        return $admin;
    }
}
