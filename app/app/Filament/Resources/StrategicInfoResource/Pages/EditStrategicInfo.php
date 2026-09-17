<?php

namespace App\Filament\Resources\StrategicInfoResource\Pages;

use App\Filament\Resources\StrategicInfoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStrategicInfo extends EditRecord
{
    protected static string $resource = StrategicInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
