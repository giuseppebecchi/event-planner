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
            $partnerOne = trim(collect([$data['first_name'] ?? null, $data['last_name'] ?? null])->filter()->implode(' '));
            $partnerTwo = trim(collect([$data['secondary_first_name'] ?? null, $data['secondary_last_name'] ?? null])->filter()->implode(' '));
            $data['name'] = trim(sprintf('Matrimonio %s & %s', $partnerOne, $partnerTwo), ' &');
        }

        if (filled($data['lead_id'] ?? null)) {
            $lead = Lead::query()
                ->select([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'secondary_first_name',
                    'secondary_last_name',
                    'secondary_email',
                    'secondary_phone',
                    'nationality',
                    'address',
                    'budget_amount',
                    'venue_included_in_budget',
                    'wedding_period',
                ])
                ->find($data['lead_id']);

            foreach ([
                'first_name',
                'last_name',
                'email',
                'phone',
                'secondary_first_name',
                'secondary_last_name',
                'secondary_email',
                'secondary_phone',
                'nationality',
                'address',
            ] as $field) {
                if (blank($data[$field] ?? null)) {
                    $data[$field] = $lead?->{$field};
                }
            }

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
