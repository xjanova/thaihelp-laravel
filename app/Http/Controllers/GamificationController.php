<?php

namespace App\Http\Controllers;

use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function __construct(
        private GamificationService $gamification
    ) {}

    /**
     * GET /api/profile - User badges, rank, stats, challenges
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $profile = $this->gamification->getUserProfile($user);

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    /**
     * GET /api/leaderboard - Top 20 users
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 20), 50);
        $leaderboard = $this->gamification->getLeaderboard($limit);

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
        ]);
    }

    /**
     * GET /api/challenges - Today's challenges + user progress
     */
    public function challenges(Request $request): JsonResponse
    {
        $challenges = $this->gamification->getDailyChallenges();
        $user = $request->user();

        $data = $challenges->map(function ($challenge) use ($user) {
            $progress = 0;
            $completed = false;

            if ($user) {
                $uc = $challenge->userChallenges()->where('user_id', $user->id)->first();
                if ($uc) {
                    $progress = $uc->progress;
                    $completed = $uc->completed;
                }
            }

            return [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'description' => $challenge->description,
                'target_type' => $challenge->target_type,
                'target_count' => $challenge->target_count,
                'reward_stars' => $challenge->reward_stars,
                'progress' => $progress,
                'completed' => $completed,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
