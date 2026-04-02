<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    public static bool $formActionsAreSticky = true;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
