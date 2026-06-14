<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    public function test_api_not_found_errors_return_standard_json_payload(): void
    {
        $response = $this->getJson('/api/v1/missing-route');

        $response
            ->assertNotFound()
            ->assertJsonStructure([
                'message',
                'status',
                'error',
            ])
            ->assertJson([
                'status' => 404,
                'error' => 'NotFoundHttpException',
            ]);
    }
}
