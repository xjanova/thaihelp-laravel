<?php

namespace App\Filament\Resources\StationReportResource\Pages;

use App\Filament\Resources\StationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStationReport extends ViewRecord
{
    protected static string $resource = StationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
