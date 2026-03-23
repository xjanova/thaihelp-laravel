<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\ApiKeyPool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TtsController extends Controller
{
    /**
     * Convert text to speech audio using Edge TTS (Microsoft Neural voice).
     * Voice: th-TH-PremwadeeNeural — เสียงสาวไทยสมจริงมาก ฟรีไม่จำกัด!
     * Returns audio/mpeg binary.
     */
    public function synthesize(Request $request)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'encoding' => ['nullable', 'string', 'in:base64'],
        ]);

        $text = $validated['text'];
        if (($validated['encoding'] ?? null) === 'base64') {
            $text = base64_decode($text);
        }

        // Strip action tags
        $text = preg_replace('/\[.*?\]/', '', $text);
        $text = trim($text);
        if (empty($text)) {
            return response()->json(['success' => false, 'message' => 'No text'], 400);
        }

        // Limit length
        $text = mb_substr($text, 0, 300);

        // Cache audio (same text = same audio)
        $cacheKey = 'tts_edge_' . md5($text);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response($cached)
                ->header('Content-Type', 'audio/mpeg')
                ->header('Cache-Control', 'public, max-age=86400');
        }

        // Generate using Edge TTS (via Node.js edge-tts package)
        $audio = $this->edgeTts($text);

        if (!$audio) {
            // Fallback: browser Web Speech API
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'ใช้เสียงจากเบราว์เซอร์แทน',
            ], 200);
        }

        // Cache for 24 hours (same text never changes)
        Cache::put($cacheKey, $audio, 86400);

        return response($audio)
            ->header('Content-Type', 'audio/mpeg')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * Microsoft Edge TTS — เสียงสาวไทยสมจริง ฟรีไม่จำกัด!
     * Voice: th-TH-PremwadeeNeural (หญิงไทย Neural)
     * Pitch: +15% (เสียงสูงขึ้น = เด็กสาว)
     * Rate: +5% (เร็วขึ้นเล็กน้อย = ร่าเริง)
     */
    private function edgeTts(string $text): ?string
    {
        $tempFile = storage_path('app/tts_' . md5($text) . '.mp3');

        try {
            // Use edge-tts CLI (Python package)
            $escapedText = escapeshellarg($text);
            $voice = 'th-TH-PremwadeeNeural';
            $pitch = '+15Hz';  // สูงขึ้น = เด็กสาว
            $rate = '+5%';     // เร็วขึ้นเล็กน้อย = ร่าเริง

            $cmd = "edge-tts --voice {$voice} --pitch {$pitch} --rate {$rate} --text {$escapedText} --write-media {$tempFile} 2>&1";

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($tempFile)) {
                Log::warning('Edge TTS failed', [
                    'code' => $returnCode,
                    'output' => implode("\n", $output),
                ]);
                return null;
            }

            $audio = file_get_contents($tempFile);
            @unlink($tempFile); // Clean up

            return $audio ?: null;
        } catch (\Exception $e) {
            Log::warning('Edge TTS error', ['error' => $e->getMessage()]);
            @unlink($tempFile);
            return null;
        }
    }
}
