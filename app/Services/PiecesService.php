<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PiecesService
{
    private PendingRequest $http;

    public function __construct()
    {
        $host = config('services.pieces.host');
        $port = config('services.pieces.port');
        $baseUrl = "http://{$host}:{$port}";

        $this->http = Http::baseUrl($baseUrl)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->retry(2, 100);
    }

    /**
     * Check if Pieces OS is reachable.
     */
    public function ping(): bool
    {
        try {
            return $this->http->get('/assets')->successful();
        } catch (\Exception $e) {
            Log::warning('Pieces OS ping failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the base URL for external use.
     */
    public function getBaseUrl(): string
    {
        $host = config('services.pieces.host');
        $port = config('services.pieces.port');

        return "http://{$host}:{$port}";
    }

    /**
     * Send a chat message to Pieces OS qGPT.
     *
     * @param  string  $message  The prompt/question to send.
     * @param  array   $context  Optional context (e.g., ['assets' => [...]]).
     * @return array{success: bool, response?: string, error?: string}
     */
    public function chat(string $message, array $context = []): array
    {
        try {
            $payload = array_merge([
                'query' => $message,
            ], $context);

            $response = $this->http->post('/api/qgpt/conversation', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'response' => $data['answer'] ?? $data['text'] ?? $response->body(),
                ];
            }

            Log::warning('Pieces OS chat error: ' . $response->status() . ' ' . $response->body());

            return [
                'success' => false,
                'error' => 'Pieces OS returned status ' . $response->status(),
            ];
        } catch (RequestException $e) {
            Log::error('Pieces OS chat request failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Request failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Pieces OS chat exception: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make a raw query to any Pieces OS API endpoint.
     *
     * @param  string  $endpoint  API path (e.g., '/api/assets').
     * @param  array   $params    Query string parameters or JSON body.
     * @param  string  $method    HTTP method (GET, POST, etc.).
     * @return array{success: bool, data?: mixed, error?: string}
     */
    public function query(string $endpoint, array $params = [], string $method = 'GET'): array
    {
        try {
            $method = strtoupper($method);

            $response = match ($method) {
                'GET' => $this->http->get($endpoint, $params),
                'POST' => $this->http->post($endpoint, $params),
                'PUT' => $this->http->put($endpoint, $params),
                'DELETE' => $this->http->delete($endpoint, $params),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Pieces OS returned status ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Pieces OS query failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
