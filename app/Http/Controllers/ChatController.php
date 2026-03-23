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
            'message' => ['required_without:messages', 'string', 'max:1000'],
            'messages' => ['required_without:message', 'array'],
            'messages.*.role' => ['required_with:messages', 'string'],
            'messages.*.content' => ['required_with:messages', 'string'],
            'history' => ['nullable', 'array'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        // Build messages array
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
            // Build location context if GPS available
            $locationContext = '';
            $lat = $validated['latitude'] ?? null;
            $lng = $validated['longitude'] ?? null;

            if ($lat && $lng) {
                // ใช้ YingContextBuilder — cache 5 นาที ประหยัด token
                $cacheKey = "ying_ctx_" . round($lat, 2) . "_" . round($lng, 2);
                $locationContext = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($lat, $lng, $request) {
                    return app(\App\Services\YingContextBuilder::class)
                        ->build((float) $lat, (float) $lng, $request->user()?->id);
                });
            }

            $groqService = app(GroqAIService::class);
            $reply = $groqService->chat($messages, $locationContext);

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

    /**
     * Build location context string with nearby stations + reports.
     */
    private function buildLocationContext(float $lat, float $lng): string
    {
        $parts = [];
        $parts[] = "📍 ตำแหน่งผู้ใช้: {$lat}, {$lng}";

        // Fetch nearby stations from Google Places (cached 5 min)
        try {
            $places = app(GooglePlacesService::class)->searchNearby($lat, $lng, 500); // 500m radius first

            if (empty($places)) {
                $places = app(GooglePlacesService::class)->searchNearby($lat, $lng, 2000); // expand to 2km
            }

            if (!empty($places)) {
                $stationList = [];
                foreach (array_slice($places, 0, 5) as $i => $station) {
                    $name = $station['name'] ?? 'ปั๊มน้ำมัน';
                    $sLat = $station['lat'] ?? $station['latitude'] ?? 0;
                    $sLng = $station['lng'] ?? $station['longitude'] ?? 0;
                    $distance = $this->haversine($lat, $lng, (float) $sLat, (float) $sLng);
                    $distStr = $distance < 1 ? round($distance * 1000) . ' ม.' : round($distance, 1) . ' กม.';
                    $placeId = $station['place_id'] ?? '';
                    $vicinity = $station['vicinity'] ?? '';

                    $stationList[] = ($i + 1) . ". {$name} ({$distStr}) [{$vicinity}] place_id:{$placeId}";
                }

                $parts[] = "⛽ ปั๊มน้ำมันใกล้ผู้ใช้:";
                $parts[] = implode("\n", $stationList);
            } else {
                $parts[] = "⛽ ไม่พบปั๊มน้ำมันในรัศมี 2 กม.";
            }
        } catch (\Exception $e) {
            $parts[] = "⛽ ไม่สามารถค้นหาปั๊มได้ (API error)";
        }

        // Check existing reports nearby
        try {
            $recentReports = \App\Models\StationReport::where('is_demo', false)
                ->where('created_at', '>=', now()->subHours(12))
                ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < 2', [
                    $lat, $lng, $lat,
                ])
                ->with('fuelReports')
                ->limit(5)
                ->get();

            if ($recentReports->isNotEmpty()) {
                $parts[] = "\n📊 รายงานล่าสุดในพื้นที่:";
                foreach ($recentReports as $r) {
                    $fuels = $r->fuelReports->map(fn($f) => "{$f->fuel_type}:{$f->status}")->implode(', ');
                    $parts[] = "- {$r->station_name}: {$fuels} (รายงานเมื่อ " . $r->created_at->diffForHumans() . ")";
                }
            }
        } catch (\Exception $e) {
            // Silent
        }

        // Check nearby incidents
        try {
            $incidents = \App\Models\Incident::where('is_active', true)
                ->where('created_at', '>=', now()->subHours(6))
                ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < 5', [
                    $lat, $lng, $lat,
                ])
                ->limit(3)
                ->get();

            if ($incidents->isNotEmpty()) {
                $parts[] = "\n🚨 เหตุการณ์ในพื้นที่:";
                foreach ($incidents as $inc) {
                    $parts[] = "- {$inc->title} ({$inc->category}) " . $inc->created_at->diffForHumans();
                }
            }
        } catch (\Exception $e) {
            // Silent
        }

        return implode("\n", $parts);
    }

    /**
     * Haversine distance in km.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
