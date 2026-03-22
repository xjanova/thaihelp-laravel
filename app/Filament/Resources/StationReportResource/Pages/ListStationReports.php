<?php

namespace App\Filament\Resources\StationReportResource\Pages;

use App\Filament\Resources\StationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStationReports extends ListRecords
{
    protected static string $resource = StationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
