<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRoutingTest extends TestCase
{
    public function test_versioned_api_index_returns_json_response(): void
    {
        $response = $this->getJson('/api/v1');

        $response
            ->assertOk()
            ->assertJson([
                'name' => 'Rokhdad API',
                'version' => 'v1',
                'status' => 'ok',
            ]);
    }
}
