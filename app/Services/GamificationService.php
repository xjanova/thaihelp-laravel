<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\DailyChallenge;
use App\Models\User;
use App\Models\UserChallenge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    /**
     * Check and award badges based on user stats.
     */
    public function checkAchievements(User $user): array
    {
        $newBadges = [];
        $existingTypes = $user->achievements()->pluck('type')->toArray();

        $checks = [
            'first_report' => $user->total_reports >= 1,
            'reporter_10' => $user->total_reports >= 10,
            'reporter_50' => $user->total_reports >= 50,
            'reporter_100' => $user->total_reports >= 100,
            'confirmer_10' => $user->total_confirmations >= 10,
            'confirmer_50' => $user->total_confirmations >= 50,
            'pwa_installed' => $user->pwa_installed,
        ];

        foreach ($checks as $type => $earned) {
            if ($earned && !in_array($type, $existingTypes) && isset(Achievement::BADGES[$type])) {
                $badge = Achievement::BADGES[$type];
                $achievement = $user->achievements()->create([
                    'type' => $type,
                    'name' => $badge['name'],
                    'description' => $badge['description'],
                    'icon' => $badge['icon'],
                    'earned_at' => now(),
                ]);
                $newBadges[] = $achievement;
            }
        }

        return $newBadges;
    }

    /**
     * Recalculate star level from reputation_score.
     */
    public function updateStarLevel(User $user): array
    {
        $score = $user->reputation_score ?? 0;
        $level = 0;

        foreach (Achievement::STAR_LEVELS as $lvl => $info) {
            if ($score >= $info['min_score']) {
                $level = $lvl;
            }
        }

        return Achievement::STAR_LEVELS[$level];
    }

    /**
     * Get today's challenges, create them if not exists.
     */
    public function getDailyChallenges(): \Illuminate\Database\Eloquent\Collection
    {
        $today = Carbon::today()->toDateString();

        $challenges = DailyChallenge::where('date', $today)
            ->where('is_active', true)
            ->get();

        if ($challenges->isEmpty()) {
            $templates = [
                [
                    'title' => 'รายงานเหตุการณ์ 2 ครั้ง',
                    'description' => 'รายงานเหตุการณ์ในชุมชนของคุณ 2 ครั้งวันนี้',
                    'target_type' => 'reports',
                    'target_count' => 2,
                    'reward_stars' => 5,
                ],
                [
                    'title' => 'ยืนยันรายงาน 3 ครั้ง',
                    'description' => 'ช่วยยืนยันรายงานของคนอื่น 3 ครั้งวันนี้',
                    'target_type' => 'confirmations',
                    'target_count' => 3,
                    'reward_stars' => 3,
                ],
                [
                    'title' => 'รายงานสถานะปั๊ม 1 ครั้ง',
                    'description' => 'รายงานสถานะปั๊มน้ำมันใกล้คุณ 1 ครั้งวันนี้',
                    'target_type' => 'stations',
                    'target_count' => 1,
                    'reward_stars' => 5,
                ],
            ];

            // Pick 3 random challenges (shuffle and take 3)
            $selected = collect($templates)->shuffle()->take(3);

            foreach ($selected as $template) {
                DailyChallenge::create(array_merge($template, [
                    'date' => $today,
                    'is_active' => true,
                ]));
            }

            $challenges = DailyChallenge::where('date', $today)
                ->where('is_active', true)
                ->get();
        }

        return $challenges;
    }

    /**
     * Increment challenge progress for a user.
     */
    public function updateChallengeProgress(User $user, string $type): void
    {
        $today = Carbon::today()->toDateString();

        $challenges = DailyChallenge::where('date', $today)
            ->where('is_active', true)
            ->where('target_type', $type)
            ->get();

        foreach ($challenges as $challenge) {
            $userChallenge = UserChallenge::firstOrCreate(
                ['user_id' => $user->id, 'challenge_id' => $challenge->id],
                ['progress' => 0, 'completed' => false]
            );

            if ($userChallenge->completed) {
                continue;
            }

            $userChallenge->increment('progress');

            if ($userChallenge->progress >= $challenge->target_count) {
                $userChallenge->update([
                    'completed' => true,
                    'completed_at' => now(),
                ]);

                // Award stars
                $user->addReputation($challenge->reward_stars);
            }
        }
    }

    /**
     * Get top users by reputation.
     */
    public function getLeaderboard(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return User::select('id', 'name', 'nickname', 'avatar_url', 'reputation_score', 'total_reports', 'total_confirmations')
            ->where('reputation_score', '>', 0)
            ->orderByDesc('reputation_score')
            ->limit($limit)
            ->get()
            ->map(function ($user, $index) {
                $starLevel = $this->updateStarLevel($user);
                return [
                    'rank' => $index + 1,
                    'id' => $user->id,
                    'nickname' => $user->nickname ?? $user->name,
                    'avatar' => $user->avatar_url,
                    'reputation' => $user->reputation_score,
                    'reports' => $user->total_reports,
                    'confirmations' => $user->total_confirmations,
                    'star_level' => $starLevel['stars'],
                    'star_name' => $starLevel['name'],
                    'star_icon' => $starLevel['icon'],
                ];
            });
    }

    /**
     * Return badges, rank, stats, challenges for a user.
     */
    public function getUserProfile(User $user): array
    {
        // Check for new achievements
        $newBadges = $this->checkAchievements($user);

        // Get all badges
        $badges = $user->achievements()->orderByDesc('earned_at')->get()->map(fn($a) => [
            'type' => $a->type,
            'name' => $a->name,
            'icon' => $a->icon,
            'description' => $a->description,
            'earned_at' => $a->earned_at->toISOString(),
        ]);

        // Star level
        $starLevel = $this->updateStarLevel($user);

        // Rank
        $rank = User::where('reputation_score', '>', $user->reputation_score)->count() + 1;

        // Today's challenges
        $challenges = $this->getDailyChallenges();
        $userChallenges = UserChallenge::where('user_id', $user->id)
            ->whereIn('challenge_id', $challenges->pluck('id'))
            ->get()
            ->keyBy('challenge_id');

        $challengeData = $challenges->map(function ($challenge) use ($userChallenges) {
            $uc = $userChallenges->get($challenge->id);
            return [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'description' => $challenge->description,
                'target_type' => $challenge->target_type,
                'target_count' => $challenge->target_count,
                'reward_stars' => $challenge->reward_stars,
                'progress' => $uc ? $uc->progress : 0,
                'completed' => $uc ? $uc->completed : false,
            ];
        });

        return [
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname ?? $user->name,
                'avatar' => $user->avatar_url,
                'reputation' => $user->reputation_score ?? 0,
                'reports_count' => $user->total_reports ?? 0,
                'confirmations_count' => $user->total_confirmations ?? 0,
            ],
            'star_level' => $starLevel,
            'rank' => $rank,
            'badges' => $badges,
            'new_badges' => collect($newBadges)->map(fn($a) => [
                'type' => $a->type,
                'name' => $a->name,
                'icon' => $a->icon,
                'description' => $a->description,
            ]),
            'challenges' => $challengeData,
            'available_badges' => collect(Achievement::BADGES)->map(fn($b, $type) => [
                'type' => $type,
                'name' => $b['name'],
                'icon' => $b['icon'],
                'description' => $b['description'],
                'earned' => $badges->contains('type', $type),
            ])->values(),
        ];
    }
}
