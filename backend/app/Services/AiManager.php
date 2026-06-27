<?php

namespace App\Services;

use App\Models\AiService;
use Illuminate\Support\Facades\Http;
use Exception;

class AiManager
{
    /**
     * Get the currently active AI Service configuration.
     *
     * @return AiService|null
     */
    public function getActiveService(): ?AiService
    {
        return AiService::where('is_active', true)->first();
    }

    /**
     * Send a chat completion request to the active AI service.
     *
     * @param array $messages
     * @param array $options Additional options like temperature, max_tokens, etc.
     * @return array
     * @throws Exception
     */
    public function chatCompletions(array $messages, array $options = []): array
    {
        $service = $this->getActiveService();

        if (!$service) {
            throw new Exception("No active AI service configured.");
        }

        $url = rtrim($service->base_url, '/') . '/chat/completions';

        $payload = array_merge([
            'model' => $service->model_name ?? 'gpt-3.5-turbo',
            'messages' => $messages,
        ], $options);

        $response = Http::withToken($service->api_key)
            ->post($url, $payload);

        if ($response->failed()) {
            throw new Exception("AI Service Error: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Helper to simply generate text given a single prompt string.
     *
     * @param string $prompt
     * @return string
     * @throws Exception
     */
    public function generateText(string $prompt): string
    {
        $response = $this->chatCompletions([
            ['role' => 'user', 'content' => $prompt]
        ]);

        return $response['choices'][0]['message']['content'] ?? '';
    }
}
