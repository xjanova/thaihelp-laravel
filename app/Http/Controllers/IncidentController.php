<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentVote;
use App\Services\DiscordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    /**
     * API: List active incidents.
     */
    public function apiIndex(): JsonResponse
    {
        try {
            $incidents = Incident::active()
                ->with('user:id,nickname,avatar_url')
                ->latest()
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $incidents,
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'image_url' => ['nullable', 'url', 'max:500'],
        ]);

        try {
            $incident = Incident::create([
                'user_id' => $request->user()?->id,
                'category' => $validated['category'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'image_url' => $validated['image_url'] ?? null,
                'upvotes' => 0,
                'is_active' => true,
                'expires_at' => now()->addHours(4),
            ]);

            $incident->load('user:id,nickname,avatar_url');

            // Send Discord notification
            try {
                app(DiscordService::class)->notifyNewIncident($incident);
            } catch (\Exception $e) {
                Log::warning('Discord notification failed', ['error' => $e->getMessage()]);
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
     * API: Vote on an incident.
     */
    public function vote(Incident $incident, Request $request): JsonResponse
    {
        try {
            $userIp = $request->ip();

            // Check if this IP already voted on this incident
            $alreadyVoted = IncidentVote::where('incident_id', $incident->id)
                ->where('user_ip', $userIp)
                ->exists();

            if ($alreadyVoted) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณโหวตรายงานนี้ไปแล้ว',
                ], 409);
            }

            IncidentVote::create([
                'incident_id' => $incident->id,
                'user_ip' => $userIp,
            ]);

            $incident->increment('upvotes');

            return response()->json([
                'success' => true,
                'message' => 'โหวตสำเร็จ!',
                'data' => [
                    'upvotes' => $incident->fresh()->upvotes,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to vote on incident', [
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถโหวตได้ กรุณาลองใหม่',
            ], 500);
        }
    }
}
