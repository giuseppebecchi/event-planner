<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['name'] ?? null)) {
            $partnerOne = trim((string) ($data['partner_one_name'] ?? ''));
            $partnerTwo = trim((string) ($data['partner_two_name'] ?? ''));
            $data['name'] = trim(sprintf('Matrimonio %s & %s', $partnerOne, $partnerTwo), ' &');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
