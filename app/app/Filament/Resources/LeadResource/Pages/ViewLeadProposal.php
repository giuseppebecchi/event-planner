<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Models\Lead;
use App\Models\Template;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

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

    public function configureProposalAction(): Action
    {
        return Action::make('configureProposal')
            ->label('Configure proposal')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('gray')
            ->modalWidth('7xl')
            ->fillForm(fn (): array => $this->proposalConfigurationFormData())
            ->form([
                RichEditor::make('proposal_wedding_planning_service')
                    ->label('Wedding planning service')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'bulletList',
                        'orderedList',
                        'undo',
                        'redo',
                    ])
                    ->required()
                    ->columnSpanFull(),
                RichEditor::make('proposal_content')
                    ->label('Conditions')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'h2',
                        'h3',
                        'bulletList',
                        'orderedList',
                        'undo',
                        'redo',
                    ])
                    ->required()
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                /** @var Lead $lead */
                $lead = $this->getRecord();

                $lead->forceFill([
                    'proposal_wedding_planning_service' => trim((string) ($data['proposal_wedding_planning_service'] ?? '')),
                    'proposal_content' => trim((string) ($data['proposal_content'] ?? '')),
                ])->save();

                Notification::make()->title('Proposal configuration saved')->success()->send();
            });
    }

    public function configureProposalImagesAction(): Action
    {
        return Action::make('configureProposalImages')
            ->label('Configure images')
            ->icon('heroicon-o-photo')
            ->color('gray')
            ->modalWidth('7xl')
            ->fillForm(fn (): array => [
                'proposal_images_json_config' => $this->proposalImageConfigurationData(),
            ])
            ->form([
                Placeholder::make('proposal_images_help')
                    ->label('Proposal images')
                    ->content('Upload a custom image only where you want to replace the default proposal image. Empty fields keep the default asset. Logo and social block are fixed.')
                    ->columnSpanFull(),
                ...$this->proposalImageFields(),
            ])
            ->action(function (array $data): void {
                /** @var Lead $lead */
                $lead = $this->getRecord();

                $lead->forceFill([
                    'proposal_images_json_config' => $this->normalizeProposalImageConfig($data['proposal_images_json_config'] ?? []),
                ])->save();

                Notification::make()->title('Proposal images saved')->success()->send();
            });
    }

    protected function generatePhaseContentFromTemplate(): void
    {
        parent::generatePhaseContentFromTemplate();

        /** @var Lead $lead */
        $lead = $this->getRecord();
        $service = $lead->proposal_wedding_planning_service ?: $this->defaultWeddingPlanningService();
        $payload = [];

        if (blank($lead->proposal_wedding_planning_service) && filled($service)) {
            $payload['proposal_wedding_planning_service'] = $service;
        }

        if (filled($lead->proposal_content) && filled($service)) {
            $payload['proposal_content'] = str_replace('WEDDING PLANNING SERVICE', $service, (string) $lead->proposal_content);
        }

        if ($payload !== []) {
            $lead->forceFill($payload)->save();
        }
    }

    protected function proposalConfigurationFormData(): array
    {
        /** @var Lead $lead */
        $lead = $this->getRecord();

        return [
            'proposal_wedding_planning_service' => $lead->proposal_wedding_planning_service ?: $this->defaultWeddingPlanningService(),
            'proposal_content' => $lead->proposal_content ?: $this->defaultProposalConditions(),
        ];
    }

    protected function proposalImageFields(): array
    {
        return collect($this->proposalImageSlots())
            ->map(fn (array $slot, string $key): Section => Section::make($slot['label'])
                ->columnSpanFull()
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])
                        ->schema([
                            Placeholder::make("preview_{$key}")
                                ->label('Current image')
                                ->content(fn (): HtmlString => new HtmlString($this->proposalImagePreviewHtml($key, $slot))),
                            FileUpload::make("proposal_images_json_config.{$key}")
                                ->label('Custom replacement')
                                ->helperText('Default: '.$slot['default'])
                                ->disk('public')
                                ->directory('leads/proposal-images')
                                ->image()
                                ->maxSize(10240),
                        ]),
                ]))
            ->values()
            ->all();
    }

    protected function proposalImageConfigurationData(): array
    {
        /** @var Lead $lead */
        $lead = $this->getRecord();

        return collect($this->proposalImageSlots())
            ->mapWithKeys(fn (array $slot, string $key): array => [
                $key => $lead->proposal_images_json_config[$key] ?? null,
            ])
            ->all();
    }

    protected function normalizeProposalImageConfig(array $config): array
    {
        return collect($this->proposalImageSlots())
            ->mapWithKeys(fn (array $slot, string $key): array => [
                $key => $this->normalizeProposalImagePath($config[$key] ?? null),
            ])
            ->filter()
            ->all();
    }

    protected function normalizeProposalImagePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)->filter()->first();
        }

        return filled($path) ? (string) $path : null;
    }

    protected function defaultWeddingPlanningService(): string
    {
        return (string) (Template::query()
            ->where('slug', 'proposal-wedding-planning-service')
            ->where('language', 'en')
            ->value('content') ?? '');
    }

    protected function defaultProposalConditions(): string
    {
        return (string) (Template::query()
            ->where('slug', 'proposal')
            ->where('language', 'en')
            ->value('content') ?? '');
    }

    protected function proposalImagePreviewHtml(string $key, array $slot): string
    {
        /** @var Lead $lead */
        $lead = $this->getRecord();
        $customPath = $this->normalizeProposalImagePath($lead->proposal_images_json_config[$key] ?? null);
        $url = filled($customPath) && Storage::disk('public')->exists($customPath)
            ? Storage::disk('public')->url($customPath)
            : asset('images/proposal/'.$slot['default']);

        return sprintf(
            '<div style="display:grid;gap:.5rem;"><img src="%s" alt="%s" style="width:100%%;max-width:260px;aspect-ratio:4/3;object-fit:cover;border-radius:8px;border:1px solid #e5ded6;"><span style="font-size:12px;color:#7b7167;">%s</span></div>',
            e($url),
            e($slot['label']),
            filled($customPath) ? 'Current: custom image' : 'Current: default image'
        );
    }

    protected function proposalImageSlots(): array
    {
        return [
            'cover_bride' => ['label' => 'Cover bride image', 'default' => 'cover-bride.png'],
            'cover_venue' => ['label' => 'Cover venue image', 'default' => 'cover-venue.png'],
            'table_cypress' => ['label' => 'Prices page table image', 'default' => 'table-cypress.png'],
            'ceremony_hills' => ['label' => 'Prices page ceremony hills image', 'default' => 'ceremony-hills.png'],
            'ceremony_view' => ['label' => 'Extra services ceremony view image', 'default' => 'ceremony-view.png'],
            'dinner_garden' => ['label' => 'Extra services dinner garden image', 'default' => 'dinner-garden.png'],
            'ceremony_altar' => ['label' => 'Extra services ceremony altar image', 'default' => 'ceremony-altar.png'],
            'table_white' => ['label' => 'Extra services white table image', 'default' => 'table-white.png'],
            'table_strip' => ['label' => 'Extra services strip image', 'default' => 'table-strip.png'],
            'wedding_ceremony' => ['label' => 'Confirmation wedding ceremony image', 'default' => 'wedding-ceremony.png'],
            'olive_ceremony' => ['label' => 'Confirmation olive ceremony image', 'default' => 'olive-ceremony.png'],
            'table_film' => ['label' => 'Confirmation film table image', 'default' => 'table-film.png'],
            'table_rustic' => ['label' => 'Confirmation rustic table image', 'default' => 'table-rustic.png'],
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

    protected function getExportPdfUrl(): ?string
    {
        return route('admin.leads.proposal.pdf', ['lead' => $this->getRecord()]);
    }
}
