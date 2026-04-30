<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ViewLeadProposal extends BaseLeadPhasePage
{
    protected static ?string $breadcrumb = 'Proposal';

    protected function getPhaseContentField(): string
    {
        return 'proposal_content';
    }

    protected function getPhaseTitle(): string
    {
        return 'Proposal';
    }

    protected function getPhaseDescription(): string
    {
        return 'Proposal drafting, delivery and client feedback for this lead.';
    }

    protected function getPhaseEmptyCopy(): string
    {
        return 'No proposal content has been written yet. Start with headings, bold text and the structure you want to present to the client.';
    }

    protected function getPhaseHeaderActions(): array
    {
        return [
            Action::make('markProposalSent')
                ->label('Mark proposal as sent')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var Lead $lead */
                    $lead = $this->getRecord();

                    $lead->forceFill([
                        'proposal_sent_at' => now(),
                        'proposal_response_status' => $lead->proposal_response_status ?: 'awaiting',
                    ])->save();

                    Notification::make()->title('Proposal marked as sent')->success()->send();
                }),
            Action::make('addProposalNote')
                ->label('Add note / variation')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->form([
                    Textarea::make('note')
                        ->label('What happened?')
                        ->rows(5)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Lead $lead */
                    $lead = $this->getRecord();

                    $log = $lead->proposal_notes_log ?? [];
                    $log[] = [
                        'note' => trim((string) $data['note']),
                        'created_at' => now()->toDateTimeString(),
                    ];

                    $lead->forceFill(['proposal_notes_log' => $log])->save();

                    Notification::make()->title('Note saved')->success()->send();
                }),
            Action::make('saveClientResponse')
                ->label('Save client response')
                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->color('gray')
                ->form([
                    Select::make('proposal_response_status')
                        ->label('Client response')
                        ->options(Lead::PROPOSAL_RESPONSE_OPTIONS)
                        ->required(),
                    Textarea::make('note')
                        ->label('Optional note')
                        ->rows(4),
                ])
                ->fillForm(fn (): array => [
                    'proposal_response_status' => $this->getRecord()->proposal_response_status,
                ])
                ->action(function (array $data): void {
                    /** @var Lead $lead */
                    $lead = $this->getRecord();

                    $payload = [
                        'proposal_response_status' => $data['proposal_response_status'],
                        'proposal_response_at' => now(),
                    ];

                    if (filled($data['note'] ?? null)) {
                        $log = $lead->proposal_notes_log ?? [];
                        $log[] = [
                            'note' => trim((string) $data['note']),
                            'created_at' => now()->toDateTimeString(),
                        ];
                        $payload['proposal_notes_log'] = $log;
                    }

                    $lead->forceFill($payload)->save();

                    Notification::make()->title('Client response saved')->success()->send();
                }),
        ];
    }

    protected function getAsideData(): array
    {
        /** @var Lead $lead */
        $lead = $this->getRecord();

        return [
            'status_badges' => [
                ['label' => 'Proposal', 'value' => $lead->proposal_sent_at ? 'Sent' : 'Draft', 'tone' => 'olive'],
                ['label' => 'Client response', 'value' => Lead::PROPOSAL_RESPONSE_OPTIONS[$lead->proposal_response_status] ?? 'Not set', 'tone' => 'gold'],
            ],
            'meta' => [
                ['label' => 'Proposal sent', 'value' => $lead->proposal_sent_at?->format('d/m/Y H:i') ?: 'Not yet sent'],
                ['label' => 'Response saved', 'value' => $lead->proposal_response_at?->format('d/m/Y H:i') ?: 'No response yet'],
            ],
            'notes_log' => collect($lead->proposal_notes_log ?? [])->sortByDesc('created_at')->values()->all(),
        ];
    }
}
