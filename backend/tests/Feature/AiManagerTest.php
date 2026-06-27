<?php

namespace Tests\Feature;

use App\Models\AiService;
use App\Services\AiManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_service_returns_correct_model()
    {
        $bynara = AiService::create([
            'name' => 'Bynara',
            'base_url' => 'https://router.bynara.id/v1',
            'api_key' => 'sk-nry-123',
            'model_name' => 'gpt-3.5-turbo',
            'is_active' => true,
        ]);

        $manager = new AiManager();
        $this->assertEquals($bynara->id, $manager->getActiveService()->id);
    }

    public function test_only_one_service_can_be_active_at_a_time()
    {
        $bynara = AiService::create([
            'name' => 'Bynara',
            'base_url' => 'https://router.bynara.id/v1',
            'api_key' => 'sk-nry-123',
            'model_name' => 'gpt-3.5-turbo',
            'is_active' => true,
        ]);

        $gemini = AiService::create([
            'name' => 'Gemini',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
            'api_key' => 'AQ-abc-123',
            'model_name' => 'gemini-1.5-flash',
            'is_active' => true,
        ]);

        // When Gemini is activated, Bynara should be deactivated by the booted event
        $bynara->refresh();
        $this->assertFalse($bynara->is_active);
        $this->assertTrue($gemini->is_active);
    }

    public function test_request_throws_exception_if_no_active_service()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No active AI service configured.');

        $manager = new AiManager();
        $manager->chatCompletions([['role' => 'user', 'content' => 'hello']]);
    }
}
