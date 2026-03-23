<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TtsController extends Controller
{
    /**
     * Convert text to speech audio using Google Cloud TTS or fallback.
     * Returns audio/mpeg binary.
     */
    public function synthesize(Request $request)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
        ]);

        $text = $validated['text'];

        // Cache short phrases (like greetings)
        $cacheKey = 'tts_' . md5($text);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response($cached)
                ->header('Content-Type', 'audio/mpeg')
                ->header('Cache-Control', 'public, max-age=3600');
        }

        // Try Google Cloud TTS first
        $audio = $this->googleTts($text);

        // Fallback: return empty (browser will use Web Speech API)
        if (!$audio) {
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'ใช้เสียงจากเบราว์เซอร์แทน',
            ], 200);
        }

        // Cache for 1 hour
        Cache::put($cacheKey, $audio, 3600);

        return response($audio)
            ->header('Content-Type', 'audio/mpeg')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Google Cloud Text-to-Speech API.
     * Requires GOOGLE_CLOUD_TTS_KEY in .env or site_settings.
     */
    private function googleTts(string $text): ?string
    {
        $apiKey = SiteSetting::get('google_cloud_tts_key')
            ?: config('services.google_tts.api_key')
            ?: SiteSetting::get('google_maps_api_key'); // fallback: Maps key often has TTS enabled

        if (!$apiKey) return null;

        try {
            $response = Http::timeout(10)->post(
                "https://texttospeech.googleapis.com/v1/text:synthesize?key={$apiKey}",
                [
                    'input' => ['text' => $text],
                    'voice' => [
                        'languageCode' => 'th-TH',
                        'name' => 'th-TH-Standard-A', // Female Thai voice
                        'ssmlGender' => 'FEMALE',
                    ],
                    'audioConfig' => [
                        'audioEncoding' => 'MP3',
                        'pitch' => 2.0,        // Slightly higher pitch = younger
                        'speakingRate' => 1.05, // Slightly faster = more energetic
                    ],
                ]
            );

            if (!$response->ok()) {
                Log::warning('Google TTS failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
                return null;
            }

            $data = $response->json();
            $audioContent = $data['audioContent'] ?? null;

            return $audioContent ? base64_decode($audioContent) : null;
        } catch (\Exception $e) {
            Log::warning('Google TTS error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
