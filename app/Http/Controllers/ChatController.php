<?php

namespace App\Http\Controllers;

use App\Services\GroqAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Show the chat page.
     */
    public function index()
    {
        return view('pages.chat');
    }

    /**
     * API: Send a chat message and get AI reply.
     */
    public function apiChat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required_without:messages', 'string', 'max:1000'],
            'messages' => ['required_without:message', 'array'],
            'messages.*.role' => ['required_with:messages', 'string'],
            'messages.*.content' => ['required_with:messages', 'string'],
            'history' => ['nullable', 'array'],
        ]);

        // Build messages array from either format
        if (isset($validated['message'])) {
            $messages = [];
            if (!empty($validated['history'])) {
                foreach ($validated['history'] as $h) {
                    $messages[] = ['role' => $h['role'] ?? 'user', 'content' => $h['content'] ?? ''];
                }
            }
            $messages[] = ['role' => 'user', 'content' => $validated['message']];
        } else {
            $messages = $validated['messages'];
        }

        try {
            $groqService = app(GroqAIService::class);
            $reply = $groqService->chat($messages);

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error('Chat API failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อ AI ได้ กรุณาลองใหม่',
            ], 500);
        }
    }
}
