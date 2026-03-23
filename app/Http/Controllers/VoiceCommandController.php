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
     * Also handles fuel_report submissions from chat voice commands.
     */
    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transcript' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'fuel_report' => ['nullable', 'array'],
            'fuel_report.fuel_type' => ['nullable', 'string'],
            'fuel_report.status' => ['nullable', 'string'],
            'fuel_report.price' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'fuel_report.station_name' => ['nullable', 'string', 'max:255'],
            'fuel_report.place_id' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $voiceService = app(VoiceCommandService::class);

            // If fuel_report data is provided, process as fuel report
            if (!empty($validated['fuel_report']) && !empty($validated['latitude'])) {
                $result = $voiceService->processFuelReport($validated);

                return response()->json([
                    'success' => $result['success'],
                    'data' => [
                        'reply' => $result['reply'] ?? '',
                        'action' => 'FUEL_REPORT',
                        'report_id' => $result['report_id'] ?? null,
                    ],
                ]);
            }

            // Standard voice command processing
            $result = $voiceService->process($validated['transcript']);

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $result['reply'] ?? '',
                    'action' => $result['action'] ?? null,
                    'fuelType' => $result['fuelType'] ?? null,
                    'fuelStatus' => $result['fuelStatus'] ?? null,
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
