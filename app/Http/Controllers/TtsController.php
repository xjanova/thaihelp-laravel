<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Strip action tags + emoji
        $text = preg_replace('/\[.*?\]/', '', $text);
        $text = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{200D}\x{20E3}\x{E0020}-\x{E007F}]/u', '', $text);
        $text = preg_replace('/\s{2,}/', ' ', $text);
        $text = trim($text);
        if (empty($text)) {
            return response()->json(['success' => false, 'message' => 'No text'], 400);
        }

        // Limit length
        $text = mb_substr($text, 0, 300);

        // Read voice settings (guard against empty strings from DB)
        $voice = SiteSetting::get('tts_voice') ?: 'th-TH-PremwadeeNeural';
        $pitch = SiteSetting::get('tts_pitch') ?: '+30Hz';
        $rate  = SiteSetting::get('tts_rate')  ?: '+0%';

        Log::debug('TTS settings', compact('voice', 'pitch', 'rate'));

        // Cache audio as files (not in Redis/memory — audio is large binary)
        $cacheHash = md5($text . $voice . $pitch . $rate);
        $cacheDir = storage_path('app/tts-cache');
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $cacheFile = "{$cacheDir}/{$cacheHash}.mp3";

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            return response()->file($cacheFile, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        // Concurrency protection: only 1 TTS process at a time per text
        // Prevents fork-bomb when 100+ users request same text simultaneously
        $lockKey = "tts_lock_{$cacheHash}";
        if (Cache::has($lockKey)) {
            // Another process is generating this audio — tell client to use browser TTS
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'กำลังสร้างเสียง กรุณารอสักครู่ค่ะ',
            ], 200);
        }
        Cache::put($lockKey, true, 30); // lock for max 30s

        // Global concurrent TTS limit (prevent too many subprocesses)
        $concurrentKey = 'tts_concurrent_count';
        $maxConcurrent = 5; // max 5 TTS processes at once
        $concurrent = (int) Cache::increment($concurrentKey);
        if ($concurrent === 1) {
            Cache::put($concurrentKey, 1, 60);
        }
        if ($concurrent > $maxConcurrent) {
            Cache::decrement($concurrentKey);
            Cache::forget($lockKey);
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'ระบบเสียงมีคนใช้เยอะค่ะ ใช้เสียงเบราว์เซอร์แทนนะคะ',
            ], 200);
        }

        // Generate using Edge TTS
        $audio = null;
        try {
            $audio = $this->edgeTts($text, $voice, $pitch, $rate);
        } catch (\Exception $e) {
            Log::error('TTS synthesize exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        } finally {
            // Release locks
            Cache::forget($lockKey);
            $cur = (int) Cache::decrement($concurrentKey);
            if ($cur < 0) Cache::put($concurrentKey, 0, 60);
        }

        if (!$audio) {
            Log::warning('TTS fallback to browser', ['text' => mb_substr($text, 0, 50)]);
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'ใช้เสียงจากเบราว์เซอร์แทน',
            ], 200);
        }

        // Cache as file (24h, auto-expires by filemtime check above)
        file_put_contents($cacheFile, $audio);

        return response($audio)
            ->header('Content-Type', 'audio/mpeg')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * Microsoft Edge TTS — เสียงสาวไทยสมจริง ฟรีไม่จำกัด!
     * Uses Symfony Process (works even when exec() is disabled)
     */
    private function edgeTts(string $text, string $voice, string $pitch, string $rate): ?string
    {
        $tempFile = sys_get_temp_dir() . '/thaihelp_tts_' . md5($text . $voice) . '.mp3';

        try {
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
            // Don't setEnv — it clears all inherited env vars!
            // edge-tts is at /usr/local/bin which is in default PATH
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
            return $this->edgeTtsPython($text, $tempFile, $voice, $pitch, $rate);
        }
    }

    /**
     * Fallback: run edge-tts via Python subprocess.
     * SECURITY: Text passed via temp file to prevent command injection.
     */
    private function edgeTtsPython(string $text, string $tempFile, string $voice, string $pitch, string $rate): ?string
    {
        $textFile = null;
        try {
            // Write text to a temp file instead of interpolating into Python code
            $textFile = tempnam(sys_get_temp_dir(), 'tts_txt_');
            file_put_contents($textFile, $text);

            $pyScript = <<<'PYTHON'
import asyncio, sys, edge_tts
text_file, voice, pitch, rate, out_file = sys.argv[1:6]
with open(text_file, 'r', encoding='utf-8') as f:
    text = f.read()
async def main():
    c = edge_tts.Communicate(text, voice, pitch=pitch, rate=rate)
    await c.save(out_file)
asyncio.run(main())
PYTHON;

            $process = new \Symfony\Component\Process\Process([
                'python3', '-c', $pyScript,
                $textFile, $voice, $pitch, $rate, $tempFile,
            ]);
            $process->setTimeout(15);
            $process->run();

            @unlink($textFile);

            if (file_exists($tempFile)) {
                $audio = file_get_contents($tempFile);
                @unlink($tempFile);
                return $audio ?: null;
            }

            Log::warning('Edge TTS Python fallback failed', ['err' => $process->getErrorOutput()]);
            return null;
        } catch (\Exception $e) {
            Log::warning('Edge TTS Python error', ['error' => $e->getMessage()]);
            if ($textFile) @unlink($textFile);
            @unlink($tempFile);
            return null;
        }
    }
}
