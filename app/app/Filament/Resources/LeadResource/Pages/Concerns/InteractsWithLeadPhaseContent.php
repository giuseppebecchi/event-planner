<?php

namespace App\Filament\Resources\LeadResource\Pages\Concerns;

use App\Models\Lead;
use Filament\Notifications\Notification;

trait InteractsWithLeadPhaseContent
{
    public string $phaseContentDraft = '';

    abstract protected function getPhaseContentField(): string;

    public function savePhaseContent(): void
    {
        /** @var Lead $lead */
        $lead = $this->getRecord();

        $field = $this->getPhaseContentField();

        validator([
            'content' => $this->phaseContentDraft,
        ], [
            'content' => ['nullable', 'string'],
        ])->validate();

        $lead->forceFill([
            $field => trim($this->phaseContentDraft) !== '' ? trim($this->phaseContentDraft) : null,
        ])->save();

        Notification::make()
            ->title('Content updated')
            ->success()
            ->send();
    }
}
