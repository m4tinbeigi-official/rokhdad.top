<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\AiSearchQuery;
use App\Models\City;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|max:1000',
        ]);

        $userQuery = $validated['query'];
        $bynaraConfig = config('services.bynara');

        if (empty($bynaraConfig['key'])) {
            return response()->json([
                'message' => 'AI Service is not configured properly.',
            ], 500);
        }

        try {
            // Define the prompt to instruct the LLM
            $systemPrompt = "You are a helpful assistant that extracts search parameters from a Persian query about events.
Your goal is to output a JSON object with the following optional keys:
- 'q': The main search keyword (e.g. startup, artificial intelligence, marketing).
- 'city': The english slug of the city mentioned (e.g. 'tehran', 'mashhad', 'isfahan', 'shiraz').
- 'category': The english slug of the category (e.g. 'technology', 'business', 'art', 'education').
- 'event_type': 'in_person' (حضوری), 'online' (آنلاین، وبینار), or 'hybrid' (ترکیبی).

Rules:
1. ONLY output valid JSON. Do not include markdown code blocks or explanations.
2. If a parameter is not mentioned, omit it from the JSON.
3. Translate Persian city names and categories to their standard English slugs.

Example 1:
Query: رویدادهای استارتاپی تهران تو این هفته";

            // First check internal cache for this exact query
            $cached = AiSearchQuery::where('user_query', $userQuery)->first();
            if ($cached) {
                $cached->increment('usage_count');
                $extractedFilters = $cached->extracted_filters;
                $searchRequest = new Request($extractedFilters);
                $searchRequest->merge(['per_page' => 3]);
                return app(EventController::class)->index($searchRequest);
            }

            // Build dynamic prompt with active cities and categories (cached for 1h)
            $cities = Cache::remember('active_cities', 3600, fn() => City::where('is_active', true)->pluck('name', 'slug')->toArray());
            $categories = Cache::remember('active_categories', 3600, fn() => Category::where('is_active', true)->pluck('name', 'slug')->toArray());
            $cityList = implode(', ', array_keys($cities));
            $categoryList = implode(', ', array_keys($categories));
            $systemPrompt = "You are a helpful assistant that extracts search parameters from a Persian query about events.\nValid city slugs: $cityList.\nValid category slugs: $categoryList.\nYour goal is to output a JSON object with the following optional keys: 'q', 'city', 'category', 'event_type'.\nRules: ONLY output valid JSON, omit keys not mentioned, translate Persian names to the slugs above.\n";

            // Call Bynara LLM as before
            $response = Http::withToken($bynaraConfig['key'])
                ->timeout(15)
                ->post(rtrim($bynaraConfig['base_url'], '/') . '/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Query: " . $userQuery],
                    ],
                    'temperature' => 0.0,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                Log::error('Bynara API Error: ' . $response->body());
                return response()->json(['message' => 'Failed to process AI search.'], 502);
            }

            $aiData = $response->json();
            $content = $aiData['choices'][0]['message']['content'] ?? '{}';
            
            // Clean up potential markdown formatting just in case
            $content = trim($content);
            if (str_starts_with($content, '```json')) {
                $content = substr($content, 7);
                $content = substr($content, 0, -3);
            } elseif (str_starts_with($content, '```')) {
                $content = substr($content, 3);
                $content = substr($content, 0, -3);
            }

            $extractedFilters = json_decode($content, true) ?? [];

            // Store in cache table for future use
            AiSearchQuery::create([
                'user_query' => $userQuery,
                'extracted_filters' => $extractedFilters,
                'usage_count' => 1,
            ]);

            // Forward to EventController
            $searchRequest = new Request($extractedFilters);
            $searchRequest->merge(['per_page' => 3]);
            return app(EventController::class)->index($searchRequest);

        } catch (\Exception $e) {
            Log::error('AI Search Exception: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while processing your request.',
            ], 500);
        }
    }
    public function suggestions(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $results = [];

        if (strlen($q) >= 2) {
            $results = AiSearchQuery::where('user_query', 'like', "%$q%")
                ->orderByDesc('usage_count')
                ->limit(5)
                ->pluck('user_query')
                ->toArray();
        }

        if (count($results) < 5) {
            $cityNames = City::where('is_active', true)
                ->where('name', 'like', "%$q%")
                ->limit(3)
                ->pluck('name')
                ->toArray();
            $catNames = Category::where('is_active', true)
                ->where('name', 'like', "%$q%")
                ->limit(3)
                ->pluck('name')
                ->toArray();
            $results = array_merge($results, $cityNames, $catNames);
        }

        return response()->json(['suggestions' => $results]);
    }
}

