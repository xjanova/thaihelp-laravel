<?php

namespace App\Filament\Widgets;

use App\Models\StationReport;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentStationReports extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'รายงานปั๊มล่าสุด';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StationReport::query()->with(['user', 'fuelReports'])->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('station_name')
                    ->label('ชื่อปั๊ม')
                    ->icon('heroicon-m-building-storefront')
                    ->limit(30),

                Tables\Columns\TextColumn::make('reporter_display')
                    ->label('ผู้รายงาน')
                    ->icon('heroicon-m-user')
                    ->getStateUsing(fn (StationReport $record): string =>
                        $record->user?->name ?? $record->reporter_name ?? 'ไม่ระบุชื่อ'
                    ),

                Tables\Columns\TextColumn::make('fuel_reports_count')
                    ->label('จำนวนน้ำมัน')
                    ->counts('fuelReports')
                    ->badge()
                    ->color('success')
                    ->suffix(' ชนิด'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('เวลา')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
