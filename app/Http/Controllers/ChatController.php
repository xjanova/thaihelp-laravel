<?php

namespace App\Http\Controllers;

use App\Services\GroqAIService;
use App\Services\YingMemoryService;
use App\Services\YingTrainingService;
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
     * API: Send a chat message with location context + memory + learning.
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
            $lastUser = collect($messages)->where('role', 'user')->last();
            $messageText = $lastUser['content'] ?? '';
        }

        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();
        $lat = $validated['latitude'] ?? null;
        $lng = $validated['longitude'] ?? null;

        // === Memory: extract and store memories from user message ===
        $memoryService = app(YingMemoryService::class);
        try {
            $newMemories = $memoryService->extractMemories($messageText);
            foreach ($newMemories as $mem) {
                $memoryService->remember($userId, $sessionId, $mem['category'], $mem['key'], $mem['value'], $messageText);
            }
            // Track behavioral patterns
            $memoryService->trackBehavior($userId, $sessionId, $messageText, [
                'lat' => $lat, 'lng' => $lng,
            ]);
        } catch (\Exception $e) {
            // Memory should never break chat
            Log::warning('Memory processing failed', ['error' => $e->getMessage()]);
        }

        // === Build location context ===
        $locationContext = '';
        if ($lat !== null && $lng !== null) {
            try {
                $locKey = round($lat, 2) . '_' . round($lng, 2);
                $cacheKey = "ying_ctx_{$locKey}" . ($userId ? "_{$userId}" : '');

                $locationContext = Cache::remember($cacheKey, 120, function () use ($lat, $lng, $userId, $messageText) {
                    return app(\App\Services\YingContextBuilder::class)
                        ->build((float) $lat, (float) $lng, $userId, $messageText);
                });
            } catch (\Exception $e) {
                Log::warning('YingContextBuilder failed', ['error' => $e->getMessage()]);
            }
        }

        // === Memory context: inject user memories into prompt ===
        $memoryContext = '';
        try {
            $memoryContext = $memoryService->buildMemoryContext($userId, $sessionId, $messageText);
        } catch (\Exception $e) {
            Log::warning('Memory context build failed', ['error' => $e->getMessage()]);
        }

        // Combine location + memory context
        $fullContext = $locationContext . $memoryContext;

        try {
            $groqService = app(GroqAIService::class);

            if (!$groqService->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'reply' => 'ขอโทษค่ะ ยังไม่ได้ตั้งค่า API Key นะคะ กรุณาแจ้ง Admin ค่ะ',
                ], 503);
            }

            $reply = $groqService->chat($messages, $fullContext);

            // === Process REMEMBER commands from AI reply ===
            $reply = $this->processRememberCommands($reply, $userId, $sessionId, $memoryService);

            // === Collect training data ===
            try {
                app(YingTrainingService::class)->collect(
                    $userId,
                    $messageText,
                    $reply,
                    null, // system prompt is large, skip for storage efficiency
                    null, // auto-categorize
                    array_filter(['lat' => $lat, 'lng' => $lng, 'hour' => now()->hour])
                );
            } catch (\Exception $e) {
                // Training collection should never break chat
            }

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

    /**
     * Extract [REMEMBER:...] commands from AI reply and store them.
     * Strip the tags from the displayed reply.
     */
    private function processRememberCommands(string $reply, ?int $userId, ?string $sessionId, YingMemoryService $memoryService): string
    {
        return preg_replace_callback('/\[REMEMBER:(\{[^]]*\})\]/', function ($matches) use ($userId, $sessionId, $memoryService) {
            try {
                $json = json_decode($matches[1], true);
                if ($json && isset($json['cat'], $json['key'], $json['val'])) {
                    // Sanitize values — prevent storing excessively long data
                    $val = mb_substr($json['val'], 0, 500);
                    $key = mb_substr($json['key'], 0, 100);
                    $memoryService->remember($userId, $sessionId, $json['cat'], $key, $val);
                }
            } catch (\Exception $e) {
                // Silent fail
            }
            return ''; // Strip from display
        }, $reply);
    }
}
