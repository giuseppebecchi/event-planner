<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected static ?string $breadcrumb = 'Overview';

    public function getTitle(): string | Htmlable
    {
        return (string) $this->getRecordTitle();
    }

    public function getSubheading(): string | Htmlable | null
    {
        $record = $this->getRecord();

        return collect([
            $record->source ? \App\Models\Lead::SOURCE_OPTIONS[$record->source] ?? $record->source : null,
            $record->status ? \App\Models\Lead::STATUS_OPTIONS[$record->status] ?? $record->status : null,
            $record->requested_at?->format('d/m/Y'),
        ])->filter()->implode('  |  ');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
