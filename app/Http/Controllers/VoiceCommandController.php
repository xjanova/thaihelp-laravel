<?php

namespace App\Http\Controllers;

use App\Services\VoiceCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoiceCommandController extends Controller
{
    /**
     * Process a voice command transcript.
     */
    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transcript' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $voiceService = app(VoiceCommandService::class);
            $result = $voiceService->process($validated['transcript']);

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $result['reply'] ?? '',
                    'action' => $result['action'] ?? null,
                    'fuelType' => $result['fuelType'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Voice command processing failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถประมวลผลคำสั่งเสียงได้ กรุณาลองใหม่',
            ], 500);
        }
    }
}
