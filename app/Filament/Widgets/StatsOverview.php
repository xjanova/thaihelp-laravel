<?php

namespace App\Filament\Widgets;

use App\Models\FuelReport;
use App\Models\Incident;
use App\Models\StationReport;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $usersLastWeek = User::where('created_at', '<=', now()->subDays(7))->count();
        $userChange = $usersLastWeek > 0
            ? round((($totalUsers - $usersLastWeek) / $usersLastWeek) * 100, 1)
            : 0;

        $activeIncidents = Incident::where('is_active', true)->count();

        $stationReportsToday = StationReport::whereDate('created_at', today())->count();
        $stationReportsYesterday = StationReport::whereDate('created_at', today()->subDay())->count();

        $totalFuelReports = FuelReport::count();

        return [
            Stat::make('ผู้ใช้ทั้งหมด', $totalUsers)
                ->description($userChange >= 0 ? "+{$userChange}% จากสัปดาห์ก่อน" : "{$userChange}% จากสัปดาห์ก่อน")
                ->descriptionIcon($userChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userChange >= 0 ? 'success' : 'danger')
                ->chart($this->getLast7DaysCounts(User::class)),

            Stat::make('เหตุการณ์ที่กำลังเกิด', $activeIncidents)
                ->description('กำลังดำเนินอยู่')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart($this->getLast7DaysCounts(Incident::class)),

            Stat::make('รายงานปั๊มวันนี้', $stationReportsToday)
                ->description($stationReportsYesterday > 0
                    ? "เมื่อวาน: {$stationReportsYesterday} รายงาน"
                    : 'ยังไม่มีรายงานเมื่อวาน')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning')
                ->chart($this->getLast7DaysCounts(StationReport::class)),

            Stat::make('รายงานน้ำมันทั้งหมด', $totalFuelReports)
                ->description('ข้อมูลราคาและสถานะน้ำมัน')
                ->descriptionIcon('heroicon-m-fire')
                ->color('success')
                ->chart($this->getLast7DaysCounts(FuelReport::class)),
        ];
    }

    protected function getLast7DaysCounts(string $model): array
    {
        $counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $counts[] = $model::whereDate('created_at', $date)->count();
        }

        return $counts;
    }
}
