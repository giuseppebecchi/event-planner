<?php

namespace App\Filament\Resources\StrategicInfoResource\Pages;

use App\Filament\Resources\StrategicInfoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStrategicInfo extends CreateRecord
{
    protected static string $resource = StrategicInfoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
