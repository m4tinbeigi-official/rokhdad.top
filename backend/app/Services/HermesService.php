<?php

namespace App\Services;

use App\Models\HermesError;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HermesService
{
    protected string $endpoint;
    protected ?string $apiKey;

    /** Request timeout in seconds. */
    protected int $timeout = 10;

    public function __construct(string $endpoint, ?string $apiKey = null)
    {
        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey = $apiKey;
    }

    /**
     * Prepare a base HTTP request with auth headers and a timeout.
     */
    protected function baseRequest(): PendingRequest
    {
        $request = Http::acceptJson()->timeout($this->timeout);

        if ($this->apiKey) {
            $request->withToken($this->apiKey);
        }

        return $request;
    }

    /**
     * Test connection to the Hermes server (GET /ping).
     */
    public function testConnection(): bool
    {
        try {
            return $this->baseRequest()
                ->get("{$this->endpoint}/ping")
                ->successful();
        } catch (Throwable $e) {
            Log::warning('Hermes connection test failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Search the knowledge graph.
     *
     * @return array<string, mixed>
     */
    public function searchGraph(string $pattern): array
    {
        return $this->post('/search', ['pattern' => $pattern]);
    }

    /**
     * Trace a function's call path.
     *
     * @return array<string, mixed>
     */
    public function tracePath(string $function, string $direction = 'inbound'): array
    {
        return $this->post('/trace', [
            'function' => $function,
            'direction' => $direction,
        ]);
    }

    /**
     * Get a code snippet by qualified name.
     *
     * @return array<string, mixed>
     */
    public function getCodeSnippet(string $qualifiedName): array
    {
        return $this->post('/snippet', ['qualified_name' => $qualifiedName]);
    }

    /**
     * Perform a POST request, logging and persisting any failure.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function post(string $path, array $payload): array
    {
        $url = $this->endpoint . $path;

        try {
            $response = $this->baseRequest()->post($url, $payload);

            if ($response->failed()) {
                $this->recordError('hermes', "Hermes request failed: {$path}", (string) $response->body(), [
                    'url' => $url,
                    'payload' => $payload,
                    'status' => $response->status(),
                ]);

                return ['error' => 'request_failed', 'status' => $response->status()];
            }

            return (array) ($response->json() ?? []);
        } catch (Throwable $e) {
            $this->recordError('exception', $e->getMessage(), $e->getTraceAsString(), [
                'url' => $url,
                'payload' => $payload,
            ]);

            return ['error' => 'request_failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Persist an error to the hermes_errors table (best-effort) and log it.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function recordError(string $type, string $message, string $trace, array $payload = []): void
    {
        Log::error('Hermes error', ['type' => $type, 'message' => $message] + $payload);

        try {
            HermesError::create([
                'type' => $type,
                'message' => $message,
                'trace' => $trace,
                'payload' => $payload,
            ]);
        } catch (Throwable $e) {
            // Never let error-logging break the caller.
            Log::warning('Failed to persist HermesError', ['error' => $e->getMessage()]);
        }
    }
}
