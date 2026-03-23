<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentVote;
use App\Services\DiscordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class IncidentController extends Controller
{
    /**
     * Show the incident report form.
     */
    public function create()
    {
        return view('pages.report', [
            'categories' => Incident::CATEGORIES,
            'categoryLabels' => Incident::CATEGORY_LABELS,
            'categoryEmoji' => Incident::CATEGORY_EMOJI,
            'severities' => Incident::SEVERITIES,
            'severityLabels' => Incident::SEVERITY_LABELS,
        ]);
    }

    /**
     * API: List active incidents with optional radius filter.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        try {
            $query = Incident::active()
                ->with('user:id,nickname,avatar_url')
                ->latest();

            // Radius filter: only show within X km of user
            $lat = $request->query('lat');
            $lng = $request->query('lng');
            $radius = $request->query('radius', 50); // Default 50km

            if ($lat && $lng) {
                $query->withinRadius((float) $lat, (float) $lng, (float) $radius);
            }

            // Category filter
            if ($request->query('category')) {
                $query->where('category', $request->query('category'));
            }

            // Severity filter
            if ($request->query('severity')) {
                $query->where('severity', $request->query('severity'));
            }

            // Status filter
            if ($request->query('status')) {
                $query->where('status', $request->query('status'));
            }

            $incidents = $query->limit(200)->get();

            return response()->json([
                'success' => true,
                'data' => $incidents,
                'count' => $incidents->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch incidents', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถโหลดรายงานเหตุการณ์ได้',
            ], 500);
        }
    }

    /**
     * API: Create a new incident report.
     */
    public function apiStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:' . implode(',', Incident::CATEGORIES)],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'severity' => ['nullable', 'string', 'in:' . implode(',', Incident::SEVERITIES)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:500'],
            'road_name' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['url', 'max:500'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'incident_at' => ['nullable', 'date'],
            'affected_lanes' => ['nullable', 'integer', 'min:0', 'max:10'],
            'has_injuries' => ['nullable', 'boolean'],
            'emergency_notified' => ['nullable', 'boolean'],
            'report_source' => ['nullable', 'string', 'in:' . implode(',', Incident::REPORT_SOURCES)],
        ]);

        try {
            $incident = Incident::create([
                'user_id' => $request->user()?->id,
                'category' => $validated['category'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'severity' => $validated['severity'] ?? 'medium',
                'status' => 'active',
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'location_name' => $validated['location_name'] ?? null,
                'road_name' => $validated['road_name'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
                'photos' => $validated['photos'] ?? null,
                'video_url' => $validated['video_url'] ?? null,
                'incident_at' => $validated['incident_at'] ?? now(),
                'affected_lanes' => $validated['affected_lanes'] ?? null,
                'has_injuries' => $validated['has_injuries'] ?? false,
                'emergency_notified' => $validated['emergency_notified'] ?? false,
                'reporter_ip' => $request->ip(),
                'report_source' => $validated['report_source'] ?? 'app',
                'upvotes' => 0,
                'confirmation_count' => 1,
                'is_active' => true,
                'expires_at' => now()->addHours(8),
            ]);

            $incident->load('user:id,nickname,avatar_url');

            if ($request->user()) {
                $request->user()->incrementReports();
            }

            // Discord notification
            try {
                app(DiscordService::class)->notifyNewIncident($incident);
            } catch (\Exception $e) {
                Log::warning('Discord notification failed', ['error' => $e->getMessage()]);
            }

            // Breaking news check (3+ similar reports)
            try {
                $breakingNews = app(\App\Services\BreakingNewsService::class)->checkForBreakingNews($incident);
                if ($breakingNews) {
                    Log::info('Breaking news triggered', ['news_id' => $breakingNews->id]);
                }
            } catch (\Exception $e) {
                Log::warning('Breaking news check failed', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'รายงานเหตุการณ์สำเร็จ!',
                'data' => $incident,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create incident', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้างรายงานได้ กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * API: Vote / confirm an incident.
     */
    public function vote(Incident $incident, Request $request): JsonResponse
    {
        try {
            $userIp = $request->ip();

            $alreadyVoted = IncidentVote::where('incident_id', $incident->id)
                ->where('user_ip', $userIp)
                ->exists();

            if ($alreadyVoted) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณยืนยันรายงานนี้ไปแล้ว',
                ], 409);
            }

            try {
                IncidentVote::create([
                    'incident_id' => $incident->id,
                    'user_ip' => $userIp,
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณยืนยันรายงานนี้ไปแล้ว',
                ], 409);
            }

            $incident->increment('upvotes');
            $incident->addConfirmation();

            if ($request->user()) {
                $request->user()->addReputation(1);
                $request->user()->increment('total_confirmations');
            }

            return response()->json([
                'success' => true,
                'message' => 'ยืนยันรายงานสำเร็จ!',
                'data' => [
                    'upvotes' => $incident->fresh()->upvotes,
                    'confirmation_count' => $incident->fresh()->confirmation_count,
                    'status' => $incident->fresh()->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to vote', ['incident_id' => $incident->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'ไม่สามารถยืนยันได้'], 500);
        }
    }

    /**
     * API: Update own incident.
     */
    public function apiUpdate(Request $request, Incident $incident): JsonResponse
    {
        if (!$request->user() || $request->user()->id !== $incident->user_id) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์แก้ไข'], 403);
        }

        if ($incident->status === 'resolved') {
            return response()->json(['success' => false, 'message' => 'รายงานนี้คลี่คลายแล้ว'], 410);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['sometimes', 'string', 'in:' . implode(',', Incident::CATEGORIES)],
            'severity' => ['sometimes', 'string', 'in:' . implode(',', Incident::SEVERITIES)],
            'status' => ['sometimes', 'string', 'in:active,resolved'],
            'location_name' => ['nullable', 'string', 'max:500'],
            'road_name' => ['nullable', 'string', 'max:255'],
            'has_injuries' => ['nullable', 'boolean'],
            'emergency_notified' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['url', 'max:500'],
        ]);

        if (isset($validated['status']) && $validated['status'] === 'resolved') {
            $incident->resolve();
        } else {
            $incident->update($validated);
        }

        return response()->json(['success' => true, 'data' => $incident->fresh()]);
    }

    /**
     * API: Delete own incident.
     */
    public function apiDestroy(Request $request, Incident $incident): JsonResponse
    {
        if (!$request->user() || $request->user()->id !== $incident->user_id) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์ลบ'], 403);
        }

        $incident->votes()->delete();
        $incident->delete();

        return response()->json(['success' => true, 'message' => 'ลบรายงานสำเร็จ']);
    }

    /**
     * API: Resolve an incident (owner or admin).
     */
    public function resolve(Request $request, Incident $incident): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['success' => false, 'message' => 'ต้องเข้าสู่ระบบ'], 401);
        }

        // Owner or admin can resolve
        $isOwner = $request->user()->id === $incident->user_id;
        $isAdmin = $request->user()->role === 'admin';

        if (!$isOwner && !$isAdmin) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403);
        }

        $incident->resolve();

        return response()->json([
            'success' => true,
            'message' => 'รายงานถูกทำเครื่องหมายว่าคลี่คลายแล้ว',
        ]);
    }

    /**
     * API: My incidents (history).
     */
    public function myIncidents(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['success' => false, 'message' => 'ต้องเข้าสู่ระบบ'], 401);
        }

        $incidents = Incident::where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $incidents,
        ]);
    }
}
