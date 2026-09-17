<?php

namespace App\Filament\Resources\StrategicInfoResource\Pages;

use App\Filament\Resources\StrategicInfoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStrategicInfos extends ListRecords
{
    protected static string $resource = StrategicInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
