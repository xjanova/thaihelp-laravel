<?php

namespace App\Filament\Resources\StationReportResource\Pages;

use App\Filament\Resources\StationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStationReport extends EditRecord
{
    protected static string $resource = StationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
