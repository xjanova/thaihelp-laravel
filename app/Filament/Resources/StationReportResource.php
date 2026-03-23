<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StationReportResource\Pages;
use App\Models\FuelReport;
use App\Models\StationReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StationReportResource extends Resource
{
    protected static ?string $model = StationReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'รายงานปั๊ม';

    protected static ?string $modelLabel = 'รายงานปั๊ม';

    protected static ?string $pluralModelLabel = 'รายงานปั๊ม';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_demo', false)
            ->where('created_at', '>=', now()->subHours(24))
            ->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ข้อมูลปั๊ม')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('station_name')
                            ->label('ชื่อปั๊ม')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('place_id')
                            ->label('Google Place ID')
                            ->maxLength(500),

                        Forms\Components\TextInput::make('reporter_name')
                            ->label('ผู้รายงาน')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('reporter_email')
                            ->label('อีเมล')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('latitude')
                            ->label('ละติจูด')
                            ->numeric(),

                        Forms\Components\TextInput::make('longitude')
                            ->label('ลองจิจูด')
                            ->numeric(),

                        Forms\Components\Textarea::make('note')
                            ->label('หมายเหตุ')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('สถานะ')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('ยืนยันแล้ว'),

                        Forms\Components\Toggle::make('is_demo')
                            ->label('ข้อมูล Demo'),

                        Forms\Components\TextInput::make('confirmation_count')
                            ->label('จำนวนยืนยัน')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('ข้อมูลปั๊ม')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('station_name')->label('ชื่อปั๊ม'),
                        Infolists\Components\TextEntry::make('place_id')->label('Place ID'),
                        Infolists\Components\TextEntry::make('reporter_name')->label('ผู้รายงาน')->default('ไม่ระบุ'),
                        Infolists\Components\TextEntry::make('note')->label('หมายเหตุ')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('latitude')->label('ละติจูด'),
                        Infolists\Components\TextEntry::make('longitude')->label('ลองจิจูด'),
                    ]),

                Infolists\Components\Section::make('สถานะ')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\IconEntry::make('is_verified')->label('ยืนยันแล้ว')->boolean(),
                        Infolists\Components\IconEntry::make('is_demo')->label('Demo')->boolean(),
                        Infolists\Components\TextEntry::make('confirmation_count')->label('จำนวนยืนยัน')->badge()->color('success'),
                        Infolists\Components\TextEntry::make('created_at')->label('สร้างเมื่อ')->dateTime('d M Y H:i'),
                    ]),

                Infolists\Components\Section::make('รายงานน้ำมัน')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('fuelReports')
                            ->label('')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('fuel_type')
                                    ->label('ชนิด')
                                    ->formatStateUsing(fn (string $state) => FuelReport::FUEL_LABELS[$state] ?? $state),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('สถานะ')
                                    ->badge()
                                    ->color(fn (string $state) => match ($state) {
                                        'available' => 'success',
                                        'low' => 'warning',
                                        'empty' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state) => FuelReport::STATUS_LABELS[$state] ?? $state),
                                Infolists\Components\TextEntry::make('price')
                                    ->label('ราคา')
                                    ->money('THB')
                                    ->default('-'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                Tables\Columns\TextColumn::make('station_name')
                    ->label('ชื่อปั๊ม')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->note),

                Tables\Columns\TextColumn::make('reporter_name')
                    ->label('ผู้รายงาน')
                    ->searchable()
                    ->limit(15)
                    ->default('ไม่ระบุ'),

                Tables\Columns\TextColumn::make('fuel_reports_count')
                    ->counts('fuelReports')
                    ->label('น้ำมัน')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('confirmation_count')
                    ->label('👥 ยืนยัน')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('✓')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_demo')
                    ->label('Demo')
                    ->boolean()
                    ->trueIcon('heroicon-o-beaker')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-signal')
                    ->falseColor('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('เมื่อ')
                    ->dateTime('d M H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('สถานะยืนยัน')
                    ->trueLabel('ยืนยันแล้ว')
                    ->falseLabel('ยังไม่ยืนยัน'),

                Tables\Filters\TernaryFilter::make('is_demo')
                    ->label('ข้อมูล Demo')
                    ->trueLabel('Demo เท่านั้น')
                    ->falseLabel('ข้อมูลจริงเท่านั้น'),

                Tables\Filters\Filter::make('recent')
                    ->label('24 ชม. ล่าสุด')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subHours(24)))
                    ->default(true),

                Tables\Filters\Filter::make('has_fuel_reports')
                    ->label('มีรายงานน้ำมัน')
                    ->query(fn ($query) => $query->has('fuelReports')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStationReports::route('/'),
            'create' => Pages\CreateStationReport::route('/create'),
            'view' => Pages\ViewStationReport::route('/{record}'),
            'edit' => Pages\EditStationReport::route('/{record}/edit'),
        ];
    }
}
