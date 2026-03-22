<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StationReportResource\Pages;
use App\Models\StationReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StationReportResource extends Resource
{
    protected static ?string $model = StationReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('station_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('reporter_name')
                    ->maxLength(255),

                Forms\Components\TextInput::make('reporter_email')
                    ->email()
                    ->maxLength(255),

                Forms\Components\TextInput::make('place_id')
                    ->label('Google Place ID')
                    ->maxLength(255),

                Forms\Components\Textarea::make('note')
                    ->rows(3)
                    ->maxLength(1000),

                Forms\Components\TextInput::make('latitude')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('longitude')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('station_name')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('reporter_name')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('place_id')
                    ->label('Place ID')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fuel_reports_count')
                    ->counts('fuelReports')
                    ->label('Fuel Reports')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // FuelReportsRelationManager can be added here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStationReports::route('/'),
            'create' => Pages\CreateStationReport::route('/create'),
            'view' => Pages\ViewStationReport::route('/{record}'),
        ];
    }
}
