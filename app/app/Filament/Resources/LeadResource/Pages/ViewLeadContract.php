<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Models\Lead;
use App\Models\LeadDocument;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ViewLeadContract extends BaseLeadPhasePage
{
    protected static ?string $breadcrumb = 'Contract';

    protected function getPhaseContentField(): string
    {
        return 'contract_content';
    }

    protected function getPhaseTitle(): string
    {
        return 'Contract';
    }

    protected function getPhaseDescription(): string
    {
        return 'Contract delivery, signed file collection and event creation for this lead.';
    }

    protected function getPhaseEmptyCopy(): string
    {
        return 'No contract content has been written yet. Add the structure you want to manage and share internally before sending the final document.';
    }

    protected function getPhaseHeaderActions(): array
    {
        return [
            Action::make('markContractSent')
                ->label('Mark contract as sent')
                ->icon('heroicon-o-document-check')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->forceFill([
                        'contract_sent_at' => now(),
                    ])->save();

                    Notification::make()->title('Contract marked as sent')->success()->send();
                }),
            Action::make('uploadSignedContract')
                ->label('Upload signed contract')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('file_path')
                        ->label('Signed contract PDF')
                        ->disk('public')
                        ->directory('leads/documents')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(20480)
                        ->required(),
                    Toggle::make('create_project')
                        ->label('Create event after upload')
                        ->default(true)
                        ->helperText('If enabled, a project/event will be created from this lead if it does not exist yet.'),
                ])
                ->action(function (array $data): void {
                    /** @var Lead $lead */
                    $lead = $this->getRecord();

                    $document = LeadDocument::query()->create([
                        'lead_id' => $lead->id,
                        'title' => 'Signed contract - ' . ($lead->couple_name ?: 'Lead'),
                        'document_type' => 'signed_contract',
                        'file_path' => $data['file_path'],
                        'description' => 'Signed contract uploaded from Contract phase',
                        'is_shared_with_client' => false,
                        'uploaded_at' => now(),
                    ]);

                    $lead->forceFill([
                        'contract_received_at' => now(),
                        'signed_contract_document_id' => $document->id,
                    ])->save();

                    $projectCreated = false;

                    if (($data['create_project'] ?? false) && ! $lead->project()->exists()) {
                        $project = Project::query()->create([
                            'lead_id' => $lead->id,
                            'name' => $lead->couple_name ? ('Wedding - ' . $lead->couple_name) : 'Wedding project',
                            'partner_one_name' => $lead->first_name,
                            'partner_two_name' => $lead->last_name,
                            'reference_email' => $lead->email,
                            'primary_phone' => $lead->phone,
                            'nationality' => $lead->nationality,
                            'region' => $lead->desired_region,
                            'estimated_guest_count' => $lead->estimated_guest_count,
                            'budget_amount' => $lead->budget_amount,
                            'status' => 'confirmed',
                            'private_notes' => $lead->internal_notes,
                        ]);

                        $project->loadMissing('lead')->initBudget();
                        $projectCreated = true;
                    }

                    Notification::make()
                        ->title($projectCreated ? 'Signed contract uploaded and event created' : 'Signed contract uploaded')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getAsideData(): array
    {
        /** @var Lead $lead */
        $lead = $this->getRecord()->loadMissing('signedContractDocument', 'project');

        return [
            'status_badges' => [
                ['label' => 'Proposal response', 'value' => Lead::PROPOSAL_RESPONSE_OPTIONS[$lead->proposal_response_status] ?? 'Not set', 'tone' => 'gold'],
                ['label' => 'Contract', 'value' => $lead->contract_received_at ? 'Received' : ($lead->contract_sent_at ? 'Sent' : 'Pending'), 'tone' => 'rose'],
            ],
            'meta' => [
                ['label' => 'Contract sent', 'value' => $lead->contract_sent_at?->format('d/m/Y H:i') ?: 'Not yet sent'],
                ['label' => 'Signed contract received', 'value' => $lead->contract_received_at?->format('d/m/Y H:i') ?: 'Not yet received'],
            ],
            'signed_contract_url' => $lead->signedContractDocument?->file_path
                ? Storage::disk('public')->url($lead->signedContractDocument->file_path)
                : null,
            'project' => $lead->project,
        ];
    }
}
