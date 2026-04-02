<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use App\Models\LeadDocument;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ViewLeadProposalContract extends Page
{
    use InteractsWithRecord;

    protected static string $resource = LeadResource::class;

    protected string $view = 'filament.resources.lead-resource.pages.view-lead-proposal-contract';

    protected static ?string $breadcrumb = 'Proposal / Contract';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return sprintf('%s Proposal / Contract', $this->getRecordTitle());
    }

    protected function getHeaderActions(): array
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

                    Notification::make()
                        ->title('Proposal marked as sent')
                        ->success()
                        ->send();
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

                    $lead->forceFill([
                        'proposal_notes_log' => $log,
                    ])->save();

                    Notification::make()
                        ->title('Note saved')
                        ->success()
                        ->send();
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

                    Notification::make()
                        ->title('Client response saved')
                        ->success()
                        ->send();
                }),
            Action::make('markContractSent')
                ->label('Mark contract as sent')
                ->icon('heroicon-o-document-check')
                ->color('primary')
                ->visible(fn (): bool => $this->isProposalApproved())
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->forceFill([
                        'contract_sent_at' => now(),
                    ])->save();

                    Notification::make()
                        ->title('Contract marked as sent')
                        ->success()
                        ->send();
                }),
            Action::make('uploadSignedContract')
                ->label('Upload signed contract')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn (): bool => $this->isProposalApproved())
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
                ->modalDescription('Upload the signed contract. You can also confirm event creation in the same step.')
                ->action(function (array $data): void {
                    /** @var Lead $lead */
                    $lead = $this->getRecord();

                    $document = LeadDocument::query()->create([
                        'lead_id' => $lead->id,
                        'title' => 'Signed contract - ' . ($lead->couple_name ?: 'Lead'),
                        'document_type' => 'signed_contract',
                        'file_path' => $data['file_path'],
                        'description' => 'Signed contract uploaded from Proposal / Contract phase',
                        'is_shared_with_client' => false,
                        'uploaded_at' => now(),
                    ]);

                    $lead->forceFill([
                        'contract_received_at' => now(),
                        'signed_contract_document_id' => $document->id,
                    ])->save();

                    $projectCreated = false;

                    if (($data['create_project'] ?? false) && ! $lead->project()->exists()) {
                        Project::query()->create([
                            'lead_id' => $lead->id,
                            'name' => $lead->couple_name ? ('Wedding - ' . $lead->couple_name) : 'Wedding project',
                            'partner_one_name' => $lead->first_name,
                            'partner_two_name' => $lead->last_name,
                            'reference_email' => $lead->email,
                            'primary_phone' => $lead->phone,
                            'nationality' => $lead->nationality,
                            'region' => $lead->desired_region,
                            'estimated_guest_count' => $lead->estimated_guest_count,
                            'status' => 'confirmed',
                            'private_notes' => $lead->internal_notes,
                        ]);

                        $projectCreated = true;
                    }

                    Notification::make()
                        ->title($projectCreated ? 'Signed contract uploaded and event created' : 'Signed contract uploaded')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        /** @var Lead $lead */
        $lead = $this->getRecord()->loadMissing('signedContractDocument', 'project');

        $budget = [
            'vendors' => $this->sumBudget($lead->budget_vendors),
            'planner' => $this->sumBudget($lead->budget_wedding_planner),
            'extras' => $this->sumBudget($lead->budget_wedding_planner_extra_services),
            'packages' => $this->sumBudget($lead->budget_wedding_planner_special_packages),
        ];
        $budget['grand_total'] = $budget['vendors'] + $budget['planner'] + $budget['extras'] + $budget['packages'];

        return [
            'lead' => $lead,
            'budget' => $budget,
            'proposalNotesLog' => collect($lead->proposal_notes_log ?? [])->sortByDesc('created_at')->values()->all(),
            'proposalPdfUrl' => '#',
            'contractPdfUrl' => '#',
            'signedContractUrl' => $lead->signedContractDocument?->file_path ? Storage::disk('public')->url($lead->signedContractDocument->file_path) : null,
            'isProposalApproved' => $this->isProposalApproved(),
        ];
    }

    protected function isProposalApproved(): bool
    {
        return in_array($this->getRecord()->proposal_response_status, ['accepted', 'approved'], true);
    }

    protected function sumBudget(?array $rows): float
    {
        return collect($rows ?? [])
            ->sum(fn (array $row): float => (float) ($row['amount'] ?? 0));
    }
}
