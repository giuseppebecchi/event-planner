<?php

namespace App\Filament\Resources\ProposalTemplateResource\Pages;

use App\Filament\Resources\ProposalTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProposalTemplates extends ListRecords
{
    protected static string $resource = ProposalTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
