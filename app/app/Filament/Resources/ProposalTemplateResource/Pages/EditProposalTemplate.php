<?php

namespace App\Filament\Resources\ProposalTemplateResource\Pages;

use App\Filament\Resources\ProposalTemplateResource;
use Filament\Resources\Pages\EditRecord;

class EditProposalTemplate extends EditRecord
{
    protected static string $resource = ProposalTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return ProposalTemplateResource::getUrl('view', ['record' => $this->record]);
    }
}
