<?php

namespace Tests\Feature;

use App\Models\HermesError;
use App\Services\HermesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HermesServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): HermesService
    {
        return new HermesService('http://hermes.test/api', 'secret-key');
    }

    public function test_test_connection_true_on_successful_ping(): void
    {
        Http::fake(['hermes.test/api/ping' => Http::response('', 200)]);

        $this->assertTrue($this->service()->testConnection());
    }

    public function test_test_connection_false_on_failure(): void
    {
        Http::fake(['hermes.test/api/ping' => Http::response('', 500)]);

        $this->assertFalse($this->service()->testConnection());
    }

    public function test_search_graph_returns_decoded_json(): void
    {
        Http::fake(['hermes.test/api/search' => Http::response(['matches' => ['a', 'b']], 200)]);

        $result = $this->service()->searchGraph('foo');

        $this->assertSame(['a', 'b'], $result['matches']);
    }

    public function test_sends_bearer_token(): void
    {
        Http::fake(['hermes.test/api/search' => Http::response(['ok' => true], 200)]);

        $this->service()->searchGraph('foo');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret-key'));
    }

    public function test_failed_request_records_hermes_error_and_returns_error(): void
    {
        Http::fake(['hermes.test/api/trace' => Http::response('boom', 503)]);

        $result = $this->service()->tracePath('App\\Foo::bar', 'outbound');

        $this->assertSame('request_failed', $result['error']);
        $this->assertSame(503, $result['status']);
        $this->assertDatabaseHas('hermes_errors', ['type' => 'hermes']);
        $this->assertSame(1, HermesError::count());
    }

    public function test_authenticated_user_can_reach_proxy_ping(): void
    {
        Http::fake(['hermes.test/api/ping' => Http::response('', 200)]);
        config([
            'hermes.enabled' => true,
            'hermes.endpoint' => 'http://hermes.test/api',
            'hermes.api_key' => 'secret-key',
        ]);

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/hermes/ping')
            ->assertOk()
            ->assertJson(['connected' => true]);
    }

    public function test_proxy_ping_requires_auth(): void
    {
        $this->getJson('/api/v1/hermes/ping')->assertUnauthorized();
    }

    public function test_proxy_returns_404_when_disabled(): void
    {
        config(['hermes.enabled' => false]);

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/hermes/ping')
            ->assertNotFound();
    }

    public function test_sync_command_skips_when_disabled(): void
    {
        config(['hermes.enabled' => false]);
        Http::fake();

        $this->artisan('hermes:sync --now')
            ->expectsOutputToContain('disabled')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_sync_command_reports_ok_when_connected(): void
    {
        config(['hermes.enabled' => true, 'hermes.endpoint' => 'http://hermes.test/api']);
        Http::fake(['hermes.test/api/ping' => Http::response('', 200)]);

        $this->artisan('hermes:sync --now')->assertExitCode(0);
    }
}
