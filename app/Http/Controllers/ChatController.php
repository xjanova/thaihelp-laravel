<?php

namespace App\Http\Controllers;

use App\Services\GroqAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index()
    {
        return view('pages.chat');
    }

    /**
     * API: Send a chat message with location context.
     */
    public function apiChat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required_without:messages', 'string', 'max:10000'],
            'messages' => ['required_without:message', 'array'],
            'messages.*.role' => ['required_with:messages', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required_with:messages', 'string'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['nullable', 'string', 'in:user,assistant'],
            'history.*.content' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'encoding' => ['nullable', 'string', 'in:base64'],
        ]);

        // Decode base64 if encoded (bypass Cloudflare WAF for Thai text)
        $isBase64 = ($validated['encoding'] ?? null) === 'base64';

        // Build messages array
        $messageText = '';
        if (isset($validated['message'])) {
            $messageText = $isBase64 ? base64_decode($validated['message']) : $validated['message'];
            $messages = [];
            if (!empty($validated['history'])) {
                foreach ($validated['history'] as $h) {
                    $content = $isBase64 ? base64_decode($h['content'] ?? '') : ($h['content'] ?? '');
                    $messages[] = ['role' => $h['role'] ?? 'user', 'content' => $content];
                }
            }
            $messages[] = ['role' => 'user', 'content' => $messageText];
        } else {
            $messages = $validated['messages'];
            // Extract last user message for smart context
            $lastUser = collect($messages)->where('role', 'user')->last();
            $messageText = $lastUser['content'] ?? '';
        }

        // Build location context if GPS available
        $locationContext = '';
        $lat = $validated['latitude'] ?? null;
        $lng = $validated['longitude'] ?? null;

        // Fix: use !== null instead of truthiness (lat=0.0 is valid at equator)
        if ($lat !== null && $lng !== null) {
            try {
                // Cache by location only (0.01° ≈ 1.1km grid) — NOT per-message!
                // Smart loading still works inside build(), but cache is shared for same area
                $locKey = round($lat, 2) . '_' . round($lng, 2);
                $userId = $request->user()?->id;
                $cacheKey = "ying_ctx_{$locKey}" . ($userId ? "_{$userId}" : '');

                $locationContext = Cache::remember($cacheKey, 120, function () use ($lat, $lng, $userId, $messageText) {
                    return app(\App\Services\YingContextBuilder::class)
                        ->build((float) $lat, (float) $lng, $userId, $messageText);
                });
            } catch (\Exception $e) {
                Log::warning('YingContextBuilder failed', ['error' => $e->getMessage()]);
            }
        }

        try {
            $groqService = app(GroqAIService::class);

            if (!$groqService->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'reply' => 'ขอโทษค่ะ ยังไม่ได้ตั้งค่า API Key นะคะ กรุณาแจ้ง Admin ค่ะ',
                ], 503);
            }

            $reply = $groqService->chat($messages, $locationContext);

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error('Chat API failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'reply' => 'ขอโทษค่ะ ระบบขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้งนะคะ',
            ], 500);
        }
    }
}
