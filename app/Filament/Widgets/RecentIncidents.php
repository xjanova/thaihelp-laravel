<?php

namespace App\Filament\Widgets;

use App\Models\Incident;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentIncidents extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'เหตุการณ์ล่าสุด';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Incident::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('ประเภท')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'accident' => '🚗 อุบัติเหตุ',
                        'flood' => '🌊 น้ำท่วม',
                        'roadblock' => '🚧 ถนนปิด',
                        'checkpoint' => '👮 ด่านตรวจ',
                        'construction' => '🏗️ ก่อสร้าง',
                        default => '📌 อื่นๆ',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'accident' => 'danger',
                        'flood' => 'info',
                        'roadblock' => 'warning',
                        'checkpoint' => 'primary',
                        'construction' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('หัวข้อ')
                    ->limit(40),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('สถานะ')
                    ->boolean(),

                Tables\Columns\TextColumn::make('upvotes')
                    ->label('โหวต')
                    ->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('เวลา')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
