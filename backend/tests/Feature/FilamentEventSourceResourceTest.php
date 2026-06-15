<?php

namespace Tests\Feature;

use App\Filament\Resources\EventSources\Pages\CreateEventSource;
use App\Filament\Resources\EventSources\Pages\EditEventSource;
use App\Models\EventSource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentEventSourceResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_event_source_management_pages(): void
    {
        $admin = $this->adminUser();
        $source = EventSource::factory()->create();

        $this->actingAs($admin)->get('/admin/event-sources')->assertOk();
        $this->actingAs($admin)->get('/admin/event-sources/create')->assertOk();
        $this->actingAs($admin)->get("/admin/event-sources/{$source->id}/edit")->assertOk();
    }

    public function test_admin_can_create_event_source(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(CreateEventSource::class)
            ->fillForm([
                'source_key' => 'evand',
                'name' => 'Evand',
                'base_url' => 'https://evand.com',
                'api_base_url' => 'https://api.evand.com',
                'auth_type' => 'api_key',
                'status' => 'active',
                'health_status' => 'unknown',
                'consecutive_failures' => 0,
                'is_enabled' => true,
                'rate_limit_per_minute' => 60,
                'config' => ['supports_api' => true],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $source = EventSource::query()->where('source_key', 'evand')->firstOrFail();

        $this->assertSame('Evand', $source->name);
        $this->assertTrue($source->is_enabled);
        $this->assertSame(60, $source->rate_limit_per_minute);
    }

    public function test_admin_can_update_event_source(): void
    {
        $source = EventSource::factory()->create([
            'source_key' => 'eseminar',
            'name' => 'Eseminar',
        ]);

        $this->actingAs($this->adminUser());

        Livewire::test(EditEventSource::class, ['record' => $source->getRouteKey()])
            ->fillForm([
                'source_key' => 'eseminar',
                'name' => 'Eseminar Updated',
                'base_url' => 'https://eseminar.tv',
                'api_base_url' => 'https://api.eseminar.tv',
                'auth_type' => 'api_key',
                'status' => 'paused',
                'health_status' => 'degraded',
                'consecutive_failures' => 1,
                'is_enabled' => false,
                'rate_limit_per_minute' => 30,
                'config' => ['supports_api' => false],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $source->refresh();

        $this->assertSame('Eseminar Updated', $source->name);
        $this->assertSame('paused', $source->status);
        $this->assertSame('degraded', $source->health_status);
        $this->assertFalse($source->is_enabled);
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->create(['name' => 'admin', 'label' => 'Administrator']);
        $admin->assignRole($role);

        return $admin;
    }
}
