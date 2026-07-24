<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Filament\Resources\SupplierResourceSupport;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\ProjectDocument;
use App\Models\ProjectSupplierCommunication;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;

class ManageProjectBudgetCategory extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.manage-project-budget-category';

    protected static ?string $breadcrumb = 'Supplier scouting';

    protected Width|string|null $maxContentWidth = Width::Full;

    public CategoryBudget $categoryBudgetRecord;

    public array $supplierFilters = [
        'search' => '',
        'service_area' => '',
        'city' => '',
        'price_range' => '',
    ];

    public ?int $requestSupplierId = null;
    public ?int $responseProposalId = null;
    public ?int $responseSupplierId = null;
    public ?string $responseFormContext = null;

    public array $requestForm = [
        'requested_at' => '',
        'request_text' => '',
        'scouting_status' => 'contacted',
        'planner_notes' => '',
    ];

    public array $responseForm = [
        'responded_at' => '',
        'availability_status' => 'available',
        'proposed_amount' => '',
        'cost_items_json' => [],
        'proposal_summary' => '',
        'response_text' => '',
        'costs_and_conditions' => '',
        'proposed_dates' => '',
        'location_available_dates' => '',
        'scouting_status' => 'shortlist',
        'proposal_status' => 'received',
        'notes' => '',
    ];

    public array $responseExistingAttachments = [];

    public array $responseUploads = [];

    public function mount(int|string $record, int|string $categoryBudget): void
    {
        $this->record = $this->resolveRecord($record);

        $this->categoryBudgetRecord = $this->resolveCategoryBudget($categoryBudget);
    }

    public function getTitle(): string|Htmlable
    {
        return (string) ($this->categoryBudgetRecord->category?->label_it ?: $this->getRecordTitle());
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function resetSupplierFilters(): void
    {
        $this->ensureCanManageScouting();

        $this->supplierFilters = [
            'search' => '',
            'service_area' => '',
            'city' => '',
            'price_range' => '',
        ];
    }

    public function openCreateSupplierModal(): void
    {
        $this->ensureCanManageScouting();

        $this->mountAction('createSupplier');
    }

    public function startSendRequest(int $supplierId): void
    {
        $this->ensureCanManageScouting();

        $this->findSearchableSupplierOrFail($supplierId);

        $proposal = $this->findProposalBySupplier($supplierId);

        $this->requestSupplierId = $supplierId;
        $this->responseProposalId = null;
        $this->responseSupplierId = null;
        $this->responseFormContext = null;
        $this->requestForm = [
            'requested_at' => ($proposal?->requested_at ?? now())->format('Y-m-d\TH:i'),
            'request_text' => (string) ($proposal?->request_text ?? ''),
            'scouting_status' => (string) ($proposal?->scouting_status ?? 'contacted'),
            'planner_notes' => (string) ($proposal?->planner_notes ?? ''),
        ];
    }

    public function cancelSendRequest(): void
    {
        $this->ensureCanManageScouting();

        $this->requestSupplierId = null;
        $this->requestForm = [
            'requested_at' => '',
            'request_text' => '',
            'scouting_status' => 'contacted',
            'planner_notes' => '',
        ];
    }

    public function saveSendRequest(): void
    {
        $this->ensureCanManageScouting();

        $supplierId = $this->requestSupplierId;

        if (! $supplierId) {
            return;
        }

        $data = validator($this->requestForm, [
            'requested_at' => ['required', 'date'],
            'request_text' => ['required', 'string'],
            'scouting_status' => ['required', 'string'],
            'planner_notes' => ['nullable', 'string'],
        ])->validate();

        $supplier = $this->findSearchableSupplierOrFail($supplierId);

        $proposal = $this->categoryBudgetRecord->supplierProposals()
            ->firstOrNew(['supplier_id' => $supplier->id]);

        $proposal->fill([
            'supplier_id' => $supplier->id,
            'requested_at' => Carbon::parse($data['requested_at']),
            'request_text' => $data['request_text'],
            'planner_notes' => $data['planner_notes'],
            'scouting_status' => $data['scouting_status'],
            'proposal_status' => $this->proposalStatusForScoutingStatus(
                $data['scouting_status'],
                $proposal->proposal_status,
                $proposal
            ),
        ]);

        if ($proposal->proposal_status !== CategoryBudgetSupplier::STATUS_CONFIRMED) {
            $proposal->confirmed_at = null;
        }

        if (blank($proposal->availability_status)) {
            $proposal->availability_status = 'pending';
        }

        $proposal->save();

        $proposal->communications()->updateOrCreate(
            ['communication_type' => 'quote_request'],
            [
                'project_id' => $proposal->project_id,
                'supplier_id' => $proposal->supplier_id,
                'direction' => 'outgoing',
                'communication_at' => Carbon::parse($data['requested_at']),
                'subject' => 'Quote request',
                'message' => $data['request_text'],
                'notes' => $data['planner_notes'] ?: null,
            ]
        );

        $this->refreshBudgetContext();
        $this->cancelSendRequest();

        Notification::make()
            ->title('Request saved')
            ->success()
            ->send();
    }

    public function openRecordResponseModal(int $proposalId): void
    {
        $this->ensureCanManageScouting();

        $proposal = $this->findProposalById($proposalId, true);

        $this->requestSupplierId = null;
        $this->responseSupplierId = null;
        $this->responseFormContext = 'requests';
        $this->fillResponseForm($proposal);
    }

    public function startInsertAcceptedQuote(int $supplierId): void
    {
        $this->ensureCanManageScouting();

        $this->requestSupplierId = null;

        $proposal = $this->findProposalBySupplier($supplierId);

        if ($proposal) {
            $this->responseFormContext = 'supplier';
            $this->fillResponseForm($proposal);

            return;
        }

        $this->findSearchableSupplierOrFail($supplierId);

        $this->responseProposalId = null;
        $this->responseSupplierId = $supplierId;
        $this->responseFormContext = 'supplier';
        $this->responseExistingAttachments = [];
        $this->responseUploads = [];
        $costItems = $this->prefilledCostItemsForNewQuote();
        $this->responseForm = [
            'responded_at' => now()->format('Y-m-d\TH:i'),
            'availability_status' => 'available',
            'proposed_amount' => '',
            'cost_items_json' => $costItems,
            'proposal_summary' => '',
            'response_text' => '',
            'costs_and_conditions' => '',
            'proposed_dates' => '',
            'location_available_dates' => '',
            'scouting_status' => 'shortlist',
            'proposal_status' => CategoryBudgetSupplier::STATUS_RECEIVED,
            'notes' => '',
        ];
    }

    protected function fillResponseForm(CategoryBudgetSupplier $proposal): void
    {
        $this->responseProposalId = $proposal->id;
        $this->responseSupplierId = null;
        $this->responseExistingAttachments = $proposal->projectDocuments
            ->where('type', ProjectDocument::TYPE_QUOTE)
            ->values()
            ->map(fn (ProjectDocument $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'url' => Storage::disk('public')->url($document->file_path),
            ])
            ->all();
        $this->responseUploads = [];
        $this->responseForm = [
            'responded_at' => ($proposal->responded_at ?? now())->format('Y-m-d\TH:i'),
            'availability_status' => (string) ($proposal->availability_status ?? 'available'),
            'proposed_amount' => $proposal->proposed_amount !== null ? (string) $proposal->proposed_amount : '',
            'cost_items_json' => $this->normalizeCostItems($proposal->cost_items_json ?? []),
            'proposal_summary' => (string) ($proposal->proposal_summary ?? ''),
            'response_text' => (string) ($proposal->response_text ?? ''),
            'costs_and_conditions' => (string) ($proposal->costs_and_conditions ?? ''),
            'proposed_dates' => collect($proposal->proposed_dates ?? [])->implode(', '),
            'location_available_dates' => collect($proposal->location_available_dates ?? [])->implode(', '),
            'scouting_status' => (string) ($proposal->scouting_status ?? 'shortlist'),
            'proposal_status' => (string) ($proposal->proposal_status ?? CategoryBudgetSupplier::STATUS_RECEIVED),
            'notes' => (string) ($proposal->notes ?? ''),
        ];
    }

    public function cancelRecordResponse(): void
    {
        $this->ensureCanManageScouting();

        $this->responseProposalId = null;
        $this->responseSupplierId = null;
        $this->responseFormContext = null;
        $this->responseExistingAttachments = [];
        $this->responseUploads = [];
        $this->responseForm = [
            'responded_at' => '',
            'availability_status' => 'available',
            'proposed_amount' => '',
            'cost_items_json' => [],
            'proposal_summary' => '',
            'response_text' => '',
            'costs_and_conditions' => '',
            'proposed_dates' => '',
            'location_available_dates' => '',
            'scouting_status' => 'shortlist',
            'proposal_status' => 'received',
            'notes' => '',
        ];
    }

    public function removeExistingResponseAttachment(int $documentId): void
    {
        $this->ensureCanManageScouting();

        $proposal = $this->findProposalById((int) $this->responseProposalId, true);
        $document = $proposal->projectDocuments()->whereKey($documentId)->first();

        if (! $document) {
            return;
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        $this->responseExistingAttachments = collect($this->responseExistingAttachments)
            ->reject(fn (array $attachment): bool => (int) ($attachment['id'] ?? 0) === $documentId)
            ->values()
            ->all();
    }

    public function saveRecordResponse(): void
    {
        $this->ensureCanManageScouting();

        $proposalId = $this->responseProposalId;
        $supplierId = $this->responseSupplierId;

        if (! $proposalId && ! $supplierId) {
            return;
        }

        $data = validator(
            [
                'form' => $this->responseForm,
                'uploads' => $this->responseUploads,
            ],
            [
                'form.responded_at' => ['required', 'date'],
                'form.availability_status' => ['required', 'string'],
                'form.proposed_amount' => ['nullable', 'numeric'],
                'form.cost_items_json' => ['array'],
                'form.cost_items_json.*.label' => ['nullable', 'string', 'max:180'],
                'form.cost_items_json.*.amount' => ['nullable', 'numeric'],
                'form.proposal_summary' => ['nullable', 'string'],
                'form.response_text' => ['nullable', 'string'],
                'form.costs_and_conditions' => ['nullable', 'string'],
                'form.proposed_dates' => ['nullable', 'string'],
                'form.location_available_dates' => ['nullable', 'string'],
                'form.scouting_status' => ['required', 'string'],
                'form.proposal_status' => ['required', 'string'],
                'form.notes' => ['nullable', 'string'],
                'uploads' => ['array'],
                'uploads.*' => ['file', 'max:20480'],
            ]
        )->validate();

        $proposal = $proposalId
            ? $this->findProposalById($proposalId, true)
            : $this->makeProposalForSupplier($supplierId);

        $proposalStatus = $this->proposalStatusForScoutingStatus(
            $data['form']['scouting_status'],
            $data['form']['proposal_status'],
            $proposal
        );

        $proposal->fill([
            'responded_at' => Carbon::parse($data['form']['responded_at']),
            'availability_status' => $data['form']['availability_status'],
            'response_text' => $data['form']['response_text'],
            'proposed_amount' => $data['form']['proposed_amount'] !== '' ? $data['form']['proposed_amount'] : null,
            'cost_items_json' => $this->normalizeCostItems($data['form']['cost_items_json'] ?? []),
            'proposal_summary' => $data['form']['proposal_summary'],
            'costs_and_conditions' => $data['form']['costs_and_conditions'],
            'proposed_dates' => $this->explodeInlineList($data['form']['proposed_dates']),
            'location_available_dates' => $this->explodeInlineList($data['form']['location_available_dates']),
            'scouting_status' => $data['form']['scouting_status'],
            'proposal_status' => $proposalStatus,
            'notes' => $data['form']['notes'],
        ]);

        if ($proposal->proposal_status === CategoryBudgetSupplier::STATUS_REQUESTED) {
            $proposal->proposal_status = CategoryBudgetSupplier::STATUS_RECEIVED;
        }

        if ($proposal->proposal_status !== CategoryBudgetSupplier::STATUS_CONFIRMED) {
            $proposal->confirmed_at = null;
        }

        $proposal->save();

        if ($this->responseFormContext !== 'supplier' && $proposal->proposal_status === CategoryBudgetSupplier::STATUS_CONFIRMED) {
            $proposal->markAsConfirmed();
        }

        $proposal->communications()->updateOrCreate(
            ['communication_type' => 'quote_response'],
            [
                'project_id' => $proposal->project_id,
                'supplier_id' => $proposal->supplier_id,
                'direction' => 'incoming',
                'communication_at' => Carbon::parse($data['form']['responded_at']),
                'subject' => 'Quote response',
                'message' => $data['form']['response_text'] ?: null,
                'notes' => $data['form']['notes'] ?: null,
            ]
        );

        collect($this->responseUploads)->each(function ($file) use ($proposal): void {
            $storedPath = $file->store('projects/budget-proposals', 'public');

            $proposal->projectDocuments()->create([
                'project_id' => $proposal->project_id,
                'supplier_id' => $proposal->supplier_id,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Quote document',
                'document_type' => ProjectDocument::TYPE_QUOTE,
                'type' => ProjectDocument::TYPE_QUOTE,
                'file_path' => $storedPath,
                'description' => $proposal->proposal_summary ?: null,
            ]);
        });

        $this->refreshBudgetContext();
        $this->cancelRecordResponse();

        Notification::make()
            ->title('Response saved')
            ->success()
            ->send();
    }

    public function addResponseCostItem(): void
    {
        $this->ensureCanManageScouting();

        $this->responseForm['cost_items_json'] ??= [];
        $this->responseForm['cost_items_json'][] = ['label' => '', 'amount' => ''];
    }

    public function removeResponseCostItem(int $index): void
    {
        $this->ensureCanManageScouting();

        unset($this->responseForm['cost_items_json'][$index]);
        $this->responseForm['cost_items_json'] = array_values($this->responseForm['cost_items_json'] ?? []);
    }

    public function openAcceptProposalModal(int $proposalId): void
    {
        $this->ensureCanManageScouting();

        $this->mountAction('acceptProposal', ['proposal' => $proposalId]);
    }

    public function getBudgetSummary(): array
    {
        $budget = $this->categoryBudgetRecord->loadMissing('category', 'supplierProposals.supplier');
        $confirmedProposals = $budget->supplierProposals
            ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED)
            ->values();
        $proposalCount = $budget->supplierProposals->count();
        $responsesCount = $budget->supplierProposals->filter(fn (CategoryBudgetSupplier $proposal): bool => $proposal->hasResponse())->count();

        return [
            'label' => $budget->category?->label_it ?? 'Category',
            'couple_budget' => $budget->project?->budget_amount !== null ? (float) $budget->project->budget_amount : null,
            'estimated_amount' => (float) ($budget->initial_estimated_amount ?? 0),
            'comparison_amount' => $budget->comparison_amount !== null ? (float) $budget->comparison_amount : null,
            'final_amount' => $budget->final_amount !== null ? (float) $budget->final_amount : null,
            'difference_amount' => $budget->amountDifference(),
            'budget_status' => $budget->budget_status,
            'proposal_count' => $proposalCount,
            'responses_count' => $responsesCount,
            'confirmed_count' => $confirmedProposals->count(),
            'confirmed_suppliers' => $confirmedProposals
                ->map(fn (CategoryBudgetSupplier $proposal): ?string => $proposal->supplier?->name)
                ->filter()
                ->values()
                ->all(),
        ];
    }

    public function isLocationCategory(): bool
    {
        $category = $this->categoryBudgetRecord->category;

        return (int) $this->categoryBudgetRecord->category_id === Supplier::LOCATION_CATEGORY_ID
            || strcasecmp((string) ($category?->label_it ?? ''), 'Location') === 0
            || strcasecmp((string) ($category?->label ?? ''), 'Venue') === 0;
    }

    public function canExportPresentationPdf(): bool
    {
        $category = $this->categoryBudgetRecord->category;
        $labels = collect([
            $category?->label,
            $category?->label_it,
        ])
            ->filter()
            ->map(fn (string $label): string => mb_strtolower(trim($label)));

        return $this->isLocationCategory()
            || $labels->contains('catering');
    }

    public function hasSupplierSearchFilters(): bool
    {
        return collect($this->supplierFilters)
            ->contains(fn ($value): bool => filled($value));
    }

    protected function isCateringCategory(): bool
    {
        $category = $this->categoryBudgetRecord->category;

        return strcasecmp((string) ($category?->label_it ?? ''), 'Catering') === 0
            || strcasecmp((string) ($category?->label ?? ''), 'Catering') === 0;
    }

    protected function searchableSupplierCategoryIds(): array
    {
        $categoryIds = [(int) $this->categoryBudgetRecord->category_id];

        if ($this->isCateringCategory()) {
            $categoryIds[] = Supplier::LOCATION_CATEGORY_ID;
        }

        return array_values(array_unique(array_filter($categoryIds)));
    }

    public function getExistingRequests(): Collection
    {
        return $this->categoryBudgetRecord
            ->loadMissing('supplierProposals.supplier', 'supplierProposals.projectDocuments')
            ->supplierProposals
            ->sortByDesc(fn (CategoryBudgetSupplier $proposal): int => (
                ($proposal->proposal_status === CategoryBudgetSupplier::STATUS_CONFIRMED ? 3 : ($proposal->hasResponse() ? 2 : 1)) * 1000000000000
            ) + (optional($proposal->updated_at)->timestamp ?? 0))
            ->values();
    }

    public function getCostItemSuggestions(): array
    {
        return $this->categoryBudgetRecord
            ->loadMissing('supplierProposals')
            ->supplierProposals
            ->reject(fn (CategoryBudgetSupplier $proposal): bool => $this->responseProposalId !== null && (int) $proposal->id === (int) $this->responseProposalId)
            ->flatMap(fn (CategoryBudgetSupplier $proposal): array => $proposal->cost_items_json ?? [])
            ->map(fn ($item): string => trim((string) ($item['label'] ?? '')))
            ->filter()
            ->unique(fn (string $label): string => mb_strtolower($label))
            ->sort()
            ->values()
            ->all();
    }

    public function hasPrefilledResponseCostItems(): bool
    {
        return collect($this->responseForm['cost_items_json'] ?? [])
            ->contains(fn ($item): bool => is_array($item) && filled($item['label'] ?? null) && blank($item['amount'] ?? null));
    }

    public function getProposalComparison(): array
    {
        $proposals = $this->categoryBudgetRecord
            ->loadMissing('supplierProposals.supplier')
            ->supplierProposals
            ->filter(fn (CategoryBudgetSupplier $proposal): bool => collect($proposal->cost_items_json ?? [])->contains(fn ($item): bool => is_array($item) && filled($item['label'] ?? null)))
            ->values();

        $rows = $proposals
            ->flatMap(fn (CategoryBudgetSupplier $proposal): array => $proposal->cost_items_json ?? [])
            ->map(fn ($item): string => trim((string) ($item['label'] ?? '')))
            ->filter()
            ->unique(fn (string $label): string => mb_strtolower($label))
            ->sort()
            ->values()
            ->map(function (string $label) use ($proposals): array {
                $key = mb_strtolower($label);

                return [
                    'label' => $label,
                    'amounts' => $proposals
                        ->mapWithKeys(function (CategoryBudgetSupplier $proposal) use ($key): array {
                            $item = collect($proposal->cost_items_json ?? [])
                                ->first(fn ($item): bool => mb_strtolower(trim((string) ($item['label'] ?? ''))) === $key);

                            return [$proposal->id => $item['amount'] ?? null];
                        })
                        ->all(),
                ];
            })
            ->all();

        return [
            'enabled' => $proposals->isNotEmpty(),
            'proposals' => $proposals,
            'rows' => $rows,
            'totals' => $proposals
                ->mapWithKeys(fn (CategoryBudgetSupplier $proposal): array => [
                    $proposal->id => collect($proposal->cost_items_json ?? [])
                        ->filter(fn ($item): bool => is_array($item) && filled($item['label'] ?? null) && filled($item['amount'] ?? null))
                        ->sum(fn (array $item): float => (float) $item['amount']),
                ])
                ->all(),
        ];
    }

    public function comparisonPdfUrl(): string
    {
        return route('admin.projects.budget.comparison.pdf', [
            'project' => $this->getRecord(),
            'categoryBudget' => $this->categoryBudgetRecord,
        ]);
    }

    public function getSupplierResults(): Collection
    {
        $filters = $this->supplierFilters;

        if (! $this->hasSupplierSearchFilters()) {
            return collect();
        }

        $trackedSupplierIds = $this->categoryBudgetRecord
            ->supplierProposals()
            ->pluck('supplier_id')
            ->filter()
            ->all();

        return Supplier::query()
            ->where(fn ($query) => $this->whereSupplierMatchesSearchableCategories($query))
            ->when(
                filled($trackedSupplierIds),
                fn ($query) => $query->whereNotIn('id', $trackedSupplierIds)
            )
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%' . trim((string) $filters['search']) . '%';

                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', $search)
                        ->orWhere('service_area', 'like', $search)
                        ->orWhere('city', 'like', $search)
                        ->orWhere('contact_person', 'like', $search)
                        ->orWhere('style_description', 'like', $search);
                });
            })
            ->when(filled($filters['service_area'] ?? null), fn ($query) => $query->where('service_area', $filters['service_area']))
            ->when(filled($filters['city'] ?? null), fn ($query) => $query->where('city', $filters['city']))
            ->when(filled($filters['price_range'] ?? null), fn ($query) => $query->where('price_range', $filters['price_range']))
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    public function getServiceAreaOptions(): array
    {
        return Supplier::query()
            ->where(fn ($query) => $this->whereSupplierMatchesSearchableCategories($query))
            ->whereNotNull('service_area')
            ->where('service_area', '!=', '')
            ->orderBy('service_area')
            ->pluck('service_area', 'service_area')
            ->all();
    }

    public function getCityOptions(): array
    {
        return Supplier::query()
            ->where(fn ($query) => $this->whereSupplierMatchesSearchableCategories($query))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->orderBy('city')
            ->pluck('city', 'city')
            ->all();
    }

    public function getPriceRangeOptions(): array
    {
        return Supplier::query()
            ->where(fn ($query) => $this->whereSupplierMatchesSearchableCategories($query))
            ->whereNotNull('price_range')
            ->where('price_range', '!=', '')
            ->orderBy('price_range')
            ->pluck('price_range', 'price_range')
            ->all();
    }

    public function createSupplierAction(): Action
    {
        return Action::make('createSupplier')
            ->label('Add supplier')
            ->visible(fn (): bool => ! auth()->user()?->isCustomer())
            ->modalHeading('Create supplier')
            ->modalWidth(Width::FiveExtraLarge)
            ->fillForm(fn (): array => [
                'category_id' => $this->categoryBudgetRecord->category_id,
            ])
            ->form([
                Placeholder::make('category_label')
                    ->label('Service category')
                    ->content(fn (): string => (string) ($this->categoryBudgetRecord->category?->label_it ?? '')),
                ...SupplierResourceSupport::mainAndAddressSections(includeCategoryField: false),
            ])
            ->action(function (array $data): void {
                $this->ensureCanManageScouting();

                $data['category_id'] = $this->categoryBudgetRecord->category_id;

                $supplier = Supplier::query()->create($data);
                $supplier->syncCategoriesFromMainAndOther();

                $this->refreshBudgetContext();
                $this->cancelSendRequest();
                $this->startSendRequest($supplier->id);
            });
    }

    public function acceptProposalAction(): Action
    {
        return Action::make('acceptProposal')
            ->label('Mark accepted quote')
            ->color('success')
            ->visible(fn (): bool => ! auth()->user()?->isCustomer())
            ->requiresConfirmation()
            ->modalHeading('Accept this supplier quote?')
            ->modalDescription('The category budget will be marked as confirmed and the final amount will be updated from the selected proposal.')
            ->action(function (array $arguments): void {
                $this->ensureCanManageScouting();

                $proposal = $this->findProposalById((int) ($arguments['proposal'] ?? 0), true);

                if (! $proposal->hasResponse()) {
                    Notification::make()
                        ->title('Register a response before confirming the quote')
                        ->danger()
                        ->send();

                    return;
                }

                $proposal->markAsConfirmed();

                $this->refreshBudgetContext();

                Notification::make()
                    ->title('Accepted quote saved')
                    ->success()
                    ->send();
            });
    }

    protected function resolveCategoryBudget(int|string $categoryBudget): CategoryBudget
    {
        return CategoryBudget::query()
            ->where('project_id', $this->getRecord()->getKey())
            ->whereKey($categoryBudget)
            ->with([
                'category',
                'supplierProposals.supplier',
                'supplierProposals.supplier.images',
                'supplierProposals.projectDocuments',
            ])
            ->firstOrFail();
    }

    protected function ensureCanManageScouting(): void
    {
        abort_if(auth()->user()?->isCustomer(), 403);
    }

    public function getPresentationExportCount(): int
    {
        $proposals = $this->categoryBudgetRecord
            ->loadMissing('supplierProposals.projectDocuments')
            ->supplierProposals;

        $shortlisted = $proposals
            ->where('scouting_status', 'shortlist')
            ->count();

        if ($shortlisted > 0) {
            return $shortlisted;
        }

        return $proposals
            ->filter(fn (CategoryBudgetSupplier $proposal): bool => $proposal->hasResponse())
            ->count();
    }

    protected function findProposalBySupplier(int $supplierId): ?CategoryBudgetSupplier
    {
        return $this->categoryBudgetRecord
            ->supplierProposals
            ->firstWhere('supplier_id', $supplierId);
    }

    protected function makeProposalForSupplier(int $supplierId): CategoryBudgetSupplier
    {
        $supplier = $this->findSearchableSupplierOrFail($supplierId);

        return $this->categoryBudgetRecord
            ->supplierProposals()
            ->firstOrNew(['supplier_id' => $supplier->id])
            ->fill([
                'supplier_id' => $supplier->id,
                'requested_at' => now(),
                'request_text' => null,
                'scouting_status' => 'chosen',
                'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
                'availability_status' => 'available',
            ]);
    }

    protected function prefilledCostItemsForNewQuote(): array
    {
        return collect($this->getCostItemSuggestions())
            ->map(fn (string $label): array => [
                'label' => $label,
                'amount' => '',
            ])
            ->values()
            ->all();
    }

    protected function findSearchableSupplierOrFail(int $supplierId): Supplier
    {
        return Supplier::query()
            ->where(fn ($query) => $this->whereSupplierMatchesSearchableCategories($query))
            ->findOrFail($supplierId);
    }

    protected function whereSupplierMatchesSearchableCategories($query): void
    {
        $categoryIds = $this->searchableSupplierCategoryIds();

        $query
            ->whereIn('category_id', $categoryIds)
            ->orWhereHas('categories', fn ($categoriesQuery) => $categoriesQuery->whereIn('categories.id', $categoryIds));
    }

    protected function findProposalById(int $proposalId, bool $fail = false): ?CategoryBudgetSupplier
    {
        $proposal = $this->categoryBudgetRecord
            ->supplierProposals
            ->firstWhere('id', $proposalId);

        if ($proposal || ! $fail) {
            return $proposal;
        }

        abort(404);
    }

    protected function proposalStatusForScoutingStatus(
        string $scoutingStatus,
        ?string $proposalStatus,
        CategoryBudgetSupplier $proposal
    ): string {
        if ($scoutingStatus === 'chosen') {
            return CategoryBudgetSupplier::STATUS_CONFIRMED;
        }

        if ($proposalStatus === CategoryBudgetSupplier::STATUS_CONFIRMED) {
            return $proposal->hasResponse()
                ? CategoryBudgetSupplier::STATUS_RECEIVED
                : CategoryBudgetSupplier::STATUS_REQUESTED;
        }

        return $proposalStatus ?: ($proposal->hasResponse()
            ? CategoryBudgetSupplier::STATUS_RECEIVED
            : CategoryBudgetSupplier::STATUS_REQUESTED);
    }

    protected function refreshBudgetContext(): void
    {
        $this->categoryBudgetRecord = $this->resolveCategoryBudget($this->categoryBudgetRecord->getKey());
        $this->record = $this->resolveRecord($this->getRecord()->getKey());
    }

    protected function explodeInlineList(?string $value): array
    {
        return collect(preg_split('/[\n,]+/', (string) $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeCostItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'label' => trim((string) ($item['label'] ?? '')),
                'amount' => filled($item['amount'] ?? null) ? round((float) $item['amount'], 2) : null,
            ])
            ->filter(fn (array $item): bool => filled($item['label']) || $item['amount'] !== null)
            ->values()
            ->all();
    }
}
