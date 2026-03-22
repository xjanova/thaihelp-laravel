<?php

namespace App\Filament\Widgets;

use App\Models\Incident;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentIncidents extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Recent Incidents';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Incident::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'accident' => '🚗 Accident',
                        'flood' => '🌊 Flood',
                        'roadblock' => '🚧 Roadblock',
                        'checkpoint' => '👮 Checkpoint',
                        'construction' => '🏗️ Construction',
                        default => '📌 Other',
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
                    ->limit(40),

                Tables\Columns\TextColumn::make('upvotes')
                    ->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
