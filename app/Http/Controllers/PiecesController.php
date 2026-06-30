<?php

namespace App\Http\Controllers;

use App\Services\PiecesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PiecesController extends Controller
{
    public function __construct(
        private readonly PiecesService $pieces
    ) {}

    /**
     * Health check — is Pieces OS reachable?
     */
    public function ping(): JsonResponse
    {
        $alive = $this->pieces->ping();

        return response()->json([
            'success' => $alive,
            'base_url' => $this->pieces->getBaseUrl(),
            'message' => $alive ? 'Pieces OS is reachable' : 'Pieces OS is not reachable',
        ], $alive ? 200 : 503);
    }

    /**
     * Send a chat message to Pieces OS qGPT.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:10000',
            'context' => 'sometimes|array',
        ]);

        $result = $this->pieces->chat(
            $validated['message'],
            $validated['context'] ?? []
        );

        if ($result['success']) {
            return response()->json($result);
        }

        Log::warning('Pieces chat API error', ['error' => $result['error'] ?? 'unknown']);

        return response()->json($result, 502);
    }
}
