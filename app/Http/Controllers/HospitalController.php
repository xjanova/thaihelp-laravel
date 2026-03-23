<?php
namespace App\Http\Controllers;

use App\Models\HospitalReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HospitalController extends Controller {
    public function index() {
        return view('pages.hospitals');
    }

    // GET /api/hospitals - list nearby hospitals
    public function apiIndex(Request $request): JsonResponse {
        try {
            $lat = $request->query('lat');
            $lng = $request->query('lng');
            $radius = $request->query('radius', 30);

            $query = HospitalReport::with('user:id,nickname,avatar_url')
                ->latest();

            if ($lat && $lng) {
                $query->withinRadius((float)$lat, (float)$lng, (float)$radius);
            }

            // Group by google_place_id or location, take latest report per hospital
            $hospitals = $query->limit(100)->get();

            // Deduplicate by matching nearby coordinates
            $unique = collect();
            foreach ($hospitals as $h) {
                $isDup = $unique->first(function ($u) use ($h) {
                    if ($h->google_place_id && $u->google_place_id === $h->google_place_id) return true;
                    $dist = $this->haversine($u->latitude, $u->longitude, $h->latitude, $h->longitude);
                    return $dist < 0.1; // within 100m = same hospital
                });
                if (!$isDup) $unique->push($h);
            }

            return response()->json(['success' => true, 'data' => $unique->values(), 'count' => $unique->count()]);
        } catch (\Exception $e) {
            Log::error('Hospital fetch failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'ไม่สามารถโหลดข้อมูลได้'], 500);
        }
    }

    // POST /api/hospitals - report hospital status
    public function apiStore(Request $request): JsonResponse {
        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'hospital_type' => ['nullable', 'string', 'in:' . implode(',', HospitalReport::HOSPITAL_TYPES)],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'total_beds' => ['nullable', 'integer', 'min:0'],
            'available_beds' => ['nullable', 'integer', 'min:0'],
            'icu_beds' => ['nullable', 'integer', 'min:0'],
            'icu_available' => ['nullable', 'integer', 'min:0'],
            'er_status' => ['nullable', 'string', 'in:' . implode(',', HospitalReport::ER_STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $report = HospitalReport::create(array_merge($validated, [
                'user_id' => $request->user()?->id,
                'reporter_ip' => $request->ip(),
                'hospital_type' => $validated['hospital_type'] ?? 'general',
                'er_status' => $validated['er_status'] ?? 'unknown',
            ]));

            if ($request->user()) {
                $request->user()->incrementReports();
            }

            return response()->json(['success' => true, 'message' => 'รายงานสถานพยาบาลสำเร็จ!', 'data' => $report], 201);
        } catch (\Exception $e) {
            Log::error('Hospital report failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'ไม่สามารถรายงานได้'], 500);
        }
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
