<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiEventMatcher
{
    /**
     * Compare two events and return the likelihood they are the same event.
     *
     * @param Event $event1
     * @param Event $event2
     * @return array{match: bool, confidence: float, reasoning: string}
     */
    public function compare(Event $event1, Event $event2): array
    {
        $apiKey = env('GEMINI_API_KEY');
        
        if (empty($apiKey)) {
            Log::error('GeminiEventMatcher: GEMINI_API_KEY is not set.');
            return ['match' => false, 'confidence' => 0.0, 'reasoning' => 'API Key not configured.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        $prompt = "I will provide you with the details of two events. Your task is to determine if they are the EXACT SAME event, just published on different platforms (e.g. Evand and Eseminar).\n\n"
            . "Event 1:\n"
            . "Title: {$event1->title}\n"
            . "Summary: {$event1->summary}\n"
            . "Starts at: {$event1->starts_at}\n"
            . "Ends at: {$event1->ends_at}\n\n"
            . "Event 2:\n"
            . "Title: {$event2->title}\n"
            . "Summary: {$event2->summary}\n"
            . "Starts at: {$event2->starts_at}\n"
            . "Ends at: {$event2->ends_at}\n\n"
            . "Respond in strict JSON format with exactly three keys:\n"
            . "1. \"match\": boolean (true if you think they are the same event, false otherwise)\n"
            . "2. \"confidence\": float between 0.0 and 100.0 (your confidence in the match)\n"
            . "3. \"reasoning\": string (a short explanation in Persian why you made this decision)\n\n"
            . "Output JSON only, without any markdown formatting.";

        try {
            $response = Http::timeout(30)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $text = trim($text);
                
                // Sometimes Gemini wraps JSON in markdown blocks even if instructed not to
                if (str_starts_with($text, '```json')) {
                    $text = str_replace(['```json', '```'], '', $text);
                }
                
                $result = json_decode($text, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($result['match'], $result['confidence'], $result['reasoning'])) {
                    return [
                        'match' => (bool) $result['match'],
                        'confidence' => (float) $result['confidence'],
                        'reasoning' => $result['reasoning']
                    ];
                }
            }
            
            Log::error('GeminiEventMatcher API Error or Invalid JSON', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
        } catch (\Exception $e) {
            Log::error('GeminiEventMatcher Exception: ' . $e->getMessage());
        }

        return ['match' => false, 'confidence' => 0.0, 'reasoning' => 'Error contacting AI.'];
    }
}
