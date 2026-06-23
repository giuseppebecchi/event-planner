<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Lead;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = ProjectResource::normalizeEventDateFormData($data);

        if (blank($data['name'] ?? null)) {
            $partnerOne = trim((string) ($data['partner_one_name'] ?? ''));
            $partnerTwo = trim((string) ($data['partner_two_name'] ?? ''));
            $data['name'] = trim(sprintf('Matrimonio %s & %s', $partnerOne, $partnerTwo), ' &');
        }

        if (filled($data['lead_id'] ?? null)) {
            $lead = Lead::query()
                ->select(['id', 'budget_amount', 'venue_included_in_budget', 'wedding_period'])
                ->find($data['lead_id']);

            if (blank($data['budget_amount'] ?? null)) {
                $data['budget_amount'] = $lead?->budget_amount;
            }

            if (blank($data['wedding_period'] ?? null)) {
                $data['wedding_period'] = $lead?->wedding_period;
            }

            $data['venue_included_in_budget'] = (bool) ($lead?->venue_included_in_budget ?? false);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->getRecord()
            ->loadMissing('lead')
            ->initBudget();

        $this->getRecord()->syncChecklistOptionsFromTemplates();
    }
}
