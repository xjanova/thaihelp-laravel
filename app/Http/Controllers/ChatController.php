<?php

namespace App\Http\Controllers;

use App\Services\GooglePlacesService;
use App\Services\GroqAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index()
    {
        return view('pages.chat');
    }

    /**
     * API: Send a chat message with location context.
     * Fetches nearby stations from Google Places and injects into AI context.
     */
    public function apiChat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required_without:messages', 'string', 'max:10000'], // base64 ยาวกว่า plaintext ~33%
            'messages' => ['required_without:message', 'array'],
            'messages.*.role' => ['required_with:messages', 'string'],
            'messages.*.content' => ['required_with:messages', 'string'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['nullable', 'string'],
            'history.*.content' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'encoding' => ['nullable', 'string', 'in:base64'],
        ]);

        // Decode base64 if encoded (bypass Cloudflare WAF for Thai text)
        $isBase64 = ($validated['encoding'] ?? null) === 'base64';

        // Build messages array
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
        }

        // Build location context if GPS available (won't throw — all try-catched inside)
        $locationContext = '';
        $lat = $validated['latitude'] ?? null;
        $lng = $validated['longitude'] ?? null;

        if ($lat && $lng) {
            try {
                $cacheKey = "ying_ctx_" . round($lat, 2) . "_" . round($lng, 2);
                $locationContext = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($lat, $lng, $request) {
                    return app(\App\Services\YingContextBuilder::class)
                        ->build((float) $lat, (float) $lng, $request->user()?->id);
                });
            } catch (\Exception $e) {
                Log::warning('YingContextBuilder failed, continuing without context', ['error' => $e->getMessage()]);
                // Continue without context — don't break chat
            }
        }

        try {
            $groqService = app(GroqAIService::class);

            if (!$groqService->isAvailable()) {
                Log::error('Chat: No Groq API key configured');
                return response()->json([
                    'success' => true,
                    'reply' => 'ขอโทษค่ะ ยังไม่ได้ตั้งค่า API Key นะคะ กรุณาแจ้ง Admin ตั้งค่าในหลังบ้านก่อนนะคะ',
                ]);
            }

            $reply = $groqService->chat($messages, $locationContext);

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error('Chat API failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 with error message so frontend shows it in chat bubble
            return response()->json([
                'success' => true,
                'reply' => 'ขอโทษค่ะ ระบบขัดข้องชั่วคราวค่ะ ลองใหม่อีกทีนะคะ 🙏',
            ]);
        }
    }

    // buildLocationContext() removed — replaced by YingContextBuilder service
}
