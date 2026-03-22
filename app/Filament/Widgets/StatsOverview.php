<?php

namespace App\Filament\Widgets;

use App\Models\FuelReport;
use App\Models\Incident;
use App\Models\StationReport;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Active Incidents', Incident::where('is_active', true)->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Station Reports (Today)', StationReport::whereDate('created_at', today())->count())
                ->description('Reports submitted today')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning'),

            Stat::make('Total Fuel Reports', FuelReport::count())
                ->description('All fuel reports')
                ->descriptionIcon('heroicon-m-fire')
                ->color('success'),
        ];
    }
}
