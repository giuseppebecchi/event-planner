<?php

namespace App\Filament\Resources\ProposalTemplateResource\Pages;

use App\Filament\Resources\ProposalTemplateResource;
use App\Models\ProposalTemplate;
use App\Support\ProposalConfiguration;
use Filament\Resources\Pages\CreateRecord;

class CreateProposalTemplate extends CreateRecord
{
    protected static string $resource = ProposalTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $base = ProposalTemplate::find($data['base_template'] ?? null);
        unset($data['base_template']);
        $data['configuration'] = $base?->configuration ?? ProposalConfiguration::defaults();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ProposalTemplateResource::getUrl('view', ['record' => $this->record]);
    }
}
