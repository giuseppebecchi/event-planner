<?php

namespace App\Filament\Resources\LeadResource\Pages\Concerns;

use App\Models\Lead;
use Filament\Notifications\Notification;

trait InteractsWithLeadPhaseContent
{
    public string $phaseContentDraft = '';

    abstract protected function getPhaseContentField(): string;

    abstract protected function normalizePhaseEditorHtml(string $html): string;

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

        $content = trim($this->normalizePhaseEditorHtml($this->phaseContentDraft));

        $lead->forceFill([
            $field => $content !== '' ? $content : null,
        ])->save();

        Notification::make()
            ->title('Content updated')
            ->success()
            ->send();
    }
}
