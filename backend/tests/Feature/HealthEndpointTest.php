<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_ok_status(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'service' => 'rokhdad-api',
            ]);
    }

    public function test_ready_endpoint_returns_ready_status(): void
    {
        $response = $this->getJson('/api/ready');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ready',
                'service' => 'rokhdad-api',
            ]);
    }
}
