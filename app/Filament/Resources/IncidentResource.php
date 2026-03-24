<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Models\Incident;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'รายงานเหตุการณ์';

    protected static ?string $modelLabel = 'เหตุการณ์';

    protected static ?string $pluralModelLabel = 'เหตุการณ์';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return \Illuminate\Support\Facades\Cache::remember('admin_incident_badge', 60, function () {
            return static::getModel()::where('is_active', true)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count() ?: null;
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('รายละเอียด')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('ประเภท')
                            ->options(collect(Incident::CATEGORY_LABELS)->mapWithKeys(
                                fn ($label, $key) => [$key => (Incident::CATEGORY_EMOJI[$key] ?? '') . ' ' . $label]
                            ))
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('title')
                            ->label('หัวข้อ')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('รายละเอียด')
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('latitude')
                            ->label('ละติจูด')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('longitude')
                            ->label('ลองจิจูด')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('image_url')
                            ->label('URL รูปภาพ')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('สถานะ')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('เปิดใช้งาน')
                            ->default(true),

                        Forms\Components\Toggle::make('is_demo')
                            ->label('ข้อมูล Demo')
                            ->default(false),

                        Forms\Components\TextInput::make('upvotes')
                            ->label('โหวต')
                            ->numeric()
                            ->default(0),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('หมดอายุ'),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('รายละเอียด')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('category')
                            ->label('ประเภท')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string =>
                                (Incident::CATEGORY_EMOJI[$state] ?? '📌') . ' ' . (Incident::CATEGORY_LABELS[$state] ?? $state)
                            )
                            ->color(fn (string $state): string => match ($state) {
                                'accident' => 'danger',
                                'flood' => 'info',
                                'roadblock' => 'warning',
                                'checkpoint' => 'primary',
                                'construction' => 'warning',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('title')->label('หัวข้อ'),
                        Infolists\Components\TextEntry::make('description')->label('รายละเอียด')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('latitude')->label('ละติจูด'),
                        Infolists\Components\TextEntry::make('longitude')->label('ลองจิจูด'),
                        Infolists\Components\TextEntry::make('upvotes')->label('โหวต')->badge()->color('success'),
                        Infolists\Components\IconEntry::make('is_active')->label('เปิดใช้งาน')->boolean(),
                        Infolists\Components\IconEntry::make('is_demo')->label('Demo')->boolean(),
                        Infolists\Components\TextEntry::make('expires_at')->label('หมดอายุ')->dateTime('d M Y H:i'),
                        Infolists\Components\TextEntry::make('created_at')->label('สร้างเมื่อ')->dateTime('d M Y H:i'),
                        Infolists\Components\TextEntry::make('user.nickname')->label('ผู้รายงาน')->default('ไม่ระบุ'),
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

                Tables\Columns\TextColumn::make('category')
                    ->label('ประเภท')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string =>
                        (Incident::CATEGORY_EMOJI[$state] ?? '📌') . ' ' . (Incident::CATEGORY_LABELS[$state] ?? $state)
                    )
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
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('upvotes')
                    ->label('👍')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_demo')
                    ->label('Demo')
                    ->boolean()
                    ->trueIcon('heroicon-o-beaker')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-signal')
                    ->falseColor('success')
                    ->alignCenter(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('เปิด'),

                Tables\Columns\TextColumn::make('report_source')
                    ->label('แหล่ง')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'ai_ying' => 'warning',
                        'voice' => 'info',
                        'discord' => 'danger',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'ai_ying' => '🤖 AI',
                        'voice' => '🎤 เสียง',
                        'discord' => '💬 Discord',
                        default => '👤 แอป',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('เมื่อ')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->expires_at?->diffForHumans() ?? 'ไม่หมดอายุ'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('ประเภท')
                    ->options(Incident::CATEGORY_LABELS)
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('สถานะ')
                    ->trueLabel('เปิดใช้งาน')
                    ->falseLabel('ปิดใช้งาน'),

                Tables\Filters\TernaryFilter::make('is_demo')
                    ->label('ข้อมูล Demo')
                    ->trueLabel('Demo เท่านั้น')
                    ->falseLabel('ข้อมูลจริงเท่านั้น'),

                Tables\Filters\Filter::make('not_expired')
                    ->label('ยังไม่หมดอายุ')
                    ->query(fn ($query) => $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                    ->default(true),
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
            'index' => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'view' => Pages\ViewIncident::route('/{record}'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),
        ];
    }
}
