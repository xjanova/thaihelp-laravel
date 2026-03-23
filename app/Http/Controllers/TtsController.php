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

        // Generate using Edge TTS
        $audio = null;
        try {
            $audio = $this->edgeTts($text);
        } catch (\Exception $e) {
            Log::error('TTS synthesize exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        if (!$audio) {
            Log::warning('TTS fallback to browser', ['text' => mb_substr($text, 0, 50)]);
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
     * Uses Symfony Process (works even when exec() is disabled)
     */
    private function edgeTts(string $text): ?string
    {
        $tempFile = '/tmp/thaihelp_tts_' . md5($text . time()) . '.mp3';

        try {
            $voice = 'th-TH-PremwadeeNeural';
            $pitch = '+15Hz';
            $rate = '+5%';

            // Find edge-tts binary
            $edgeTtsBin = 'edge-tts';
            $searchPaths = [
                '/usr/local/bin/edge-tts',
                '/usr/bin/edge-tts',
                (getenv('HOME') ?: '/home/admin') . '/.local/bin/edge-tts',
                '/root/.local/bin/edge-tts',
            ];
            foreach ($searchPaths as $path) {
                if (file_exists($path)) { $edgeTtsBin = $path; break; }
            }

            $process = new \Symfony\Component\Process\Process([
                $edgeTtsBin,
                '--voice', $voice,
                '--pitch', $pitch,
                '--rate', $rate,
                '--text', $text,
                '--write-media', $tempFile,
            ]);
            $process->setTimeout(15);
            // Add ~/.local/bin to PATH
            $env = $process->getEnv();
            $env['PATH'] = (getenv('HOME') ?: '/home/admin') . '/.local/bin:' . (getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin');
            $process->setEnv($env);
            $process->run();

            if (!$process->isSuccessful() || !file_exists($tempFile)) {
                Log::warning('Edge TTS process failed', [
                    'exit' => $process->getExitCode(),
                    'err' => $process->getErrorOutput(),
                ]);

                // Fallback: try Python inline
                return $this->edgeTtsPython($text, $tempFile, $voice, $pitch, $rate);
            }

            $audio = file_get_contents($tempFile);
            @unlink($tempFile);
            return $audio ?: null;
        } catch (\Exception $e) {
            Log::warning('Edge TTS error', ['error' => $e->getMessage()]);
            @unlink($tempFile);

            // Fallback: try Python inline
            return $this->edgeTtsPython($text, $tempFile, 'th-TH-PremwadeeNeural', '+15Hz', '+5%');
        }
    }

    /**
     * Fallback: run edge-tts via Python subprocess
     */
    private function edgeTtsPython(string $text, string $tempFile, string $voice, string $pitch, string $rate): ?string
    {
        try {
            $escapedText = str_replace("'", "\\'", $text);
            $pyScript = <<<PYTHON
import asyncio, edge_tts
async def main():
    c = edge_tts.Communicate('{$escapedText}', '{$voice}', pitch='{$pitch}', rate='{$rate}')
    await c.save('{$tempFile}')
asyncio.run(main())
PYTHON;

            $process = new \Symfony\Component\Process\Process(['python3', '-c', $pyScript]);
            $process->setTimeout(15);
            $process->run();

            if (file_exists($tempFile)) {
                $audio = file_get_contents($tempFile);
                @unlink($tempFile);
                return $audio ?: null;
            }

            Log::warning('Edge TTS Python fallback failed', ['err' => $process->getErrorOutput()]);
            return null;
        } catch (\Exception $e) {
            Log::warning('Edge TTS Python error', ['error' => $e->getMessage()]);
            @unlink($tempFile);
            return null;
        }
    }
}
