<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    public static bool $formActionsAreSticky = true;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
