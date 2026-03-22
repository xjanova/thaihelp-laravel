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
            'messages' => ['required', 'array', 'min:1', 'max:50'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant,system'],
            'messages.*.content' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $groqService = app(GroqAIService::class);
            $reply = $groqService->chat($validated['messages']);

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $reply,
                ],
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
