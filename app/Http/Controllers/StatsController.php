<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\StationReport;
use App\Models\User;
use App\Models\BreakingNews;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index()
    {
        return view('pages.stats');
    }

    public function apiStats(): JsonResponse
    {
        // Overall stats
        $totalReports = Incident::count();
        $totalStationReports = StationReport::count();
        $totalUsers = User::count();
        $activeUsers = User::where('last_active_at', '>=', now()->subHours(24))->count();
        $pwaInstalls = User::where('pwa_installed', true)->count();
        $breakingNewsCount = BreakingNews::count();

        // Reports by category (pie chart data)
        $reportsByCategory = Incident::selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // Reports per day (last 14 days) - line chart
        $dailyReports = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $label = now()->subDays($i)->format('d/m');
            $incidents = Incident::whereDate('created_at', $date)->count();
            $stations = StationReport::whereDate('created_at', $date)->count();
            $dailyReports[] = [
                'date' => $label,
                'incidents' => $incidents,
                'stations' => $stations,
                'total' => $incidents + $stations,
            ];
        }

        // Fuel status distribution (doughnut)
        $fuelStats = \App\Models\FuelReport::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Top reporters (leaderboard)
        $topReporters = User::where('total_reports', '>', 0)
            ->orderByDesc('reputation_score')
            ->limit(10)
            ->get(['nickname', 'name', 'avatar_url', 'reputation_score', 'total_reports', 'total_confirmations']);

        // Hourly activity (bar chart - last 24h)
        $hourlyActivity = [];
        for ($h = 23; $h >= 0; $h--) {
            $start = now()->subHours($h)->startOfHour();
            $end = now()->subHours($h)->endOfHour();
            $hourlyActivity[] = [
                'hour' => $start->format('H:00'),
                'count' => Incident::whereBetween('created_at', [$start, $end])->count()
                    + StationReport::whereBetween('created_at', [$start, $end])->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_reports' => $totalReports,
                    'total_station_reports' => $totalStationReports,
                    'total_users' => $totalUsers,
                    'active_users_24h' => $activeUsers,
                    'pwa_installs' => $pwaInstalls,
                    'breaking_news' => $breakingNewsCount,
                    'total_all' => $totalReports + $totalStationReports,
                ],
                'reports_by_category' => $reportsByCategory,
                'daily_reports' => $dailyReports,
                'fuel_stats' => $fuelStats,
                'top_reporters' => $topReporters,
                'hourly_activity' => $hourlyActivity,
            ],
        ]);
    }
}
