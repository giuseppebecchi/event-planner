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
        $this->supplierFilters = [
            'search' => '',
            'service_area' => '',
            'city' => '',
            'price_range' => '',
        ];
    }

    public function openCreateSupplierModal(): void
    {
        $this->mountAction('createSupplier');
    }

    public function startSendRequest(int $supplierId): void
    {
        $proposal = $this->findProposalBySupplier($supplierId);

        $this->requestSupplierId = $supplierId;
        $this->requestForm = [
            'requested_at' => ($proposal?->requested_at ?? now())->format('Y-m-d\TH:i'),
            'request_text' => (string) ($proposal?->request_text ?? ''),
            'scouting_status' => (string) ($proposal?->scouting_status ?? 'contacted'),
            'planner_notes' => (string) ($proposal?->planner_notes ?? ''),
        ];
    }

    public function cancelSendRequest(): void
    {
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

        $supplier = Supplier::query()
            ->where('category_id', $this->categoryBudgetRecord->category_id)
            ->findOrFail($supplierId);

        $proposal = $this->categoryBudgetRecord->supplierProposals()
            ->firstOrNew(['supplier_id' => $supplier->id]);

        $proposal->fill([
            'supplier_id' => $supplier->id,
            'requested_at' => Carbon::parse($data['requested_at']),
            'request_text' => $data['request_text'],
            'planner_notes' => $data['planner_notes'],
            'scouting_status' => $data['scouting_status'],
            'proposal_status' => CategoryBudgetSupplier::STATUS_REQUESTED,
        ]);

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
        $proposal = $this->findProposalById($proposalId, true);

        $this->responseProposalId = $proposal->id;
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
            'proposal_summary' => (string) ($proposal->proposal_summary ?? ''),
            'response_text' => (string) ($proposal->response_text ?? ''),
            'costs_and_conditions' => (string) ($proposal->costs_and_conditions ?? ''),
            'proposed_dates' => collect($proposal->proposed_dates ?? [])->implode(', '),
            'location_available_dates' => collect($proposal->location_available_dates ?? [])->implode(', '),
            'scouting_status' => (string) ($proposal->scouting_status ?? 'shortlist'),
            'proposal_status' => (string) (($proposal->proposal_status === CategoryBudgetSupplier::STATUS_CONFIRMED)
                ? CategoryBudgetSupplier::STATUS_RECEIVED
                : ($proposal->proposal_status ?? CategoryBudgetSupplier::STATUS_RECEIVED)),
            'notes' => (string) ($proposal->notes ?? ''),
        ];
    }

    public function cancelRecordResponse(): void
    {
        $this->responseProposalId = null;
        $this->responseExistingAttachments = [];
        $this->responseUploads = [];
        $this->responseForm = [
            'responded_at' => '',
            'availability_status' => 'available',
            'proposed_amount' => '',
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
        $proposalId = $this->responseProposalId;

        if (! $proposalId) {
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

        $proposal = $this->findProposalById($proposalId, true);

        $proposal->fill([
            'responded_at' => Carbon::parse($data['form']['responded_at']),
            'availability_status' => $data['form']['availability_status'],
            'response_text' => $data['form']['response_text'],
            'proposed_amount' => $data['form']['proposed_amount'] !== '' ? $data['form']['proposed_amount'] : null,
            'proposal_summary' => $data['form']['proposal_summary'],
            'costs_and_conditions' => $data['form']['costs_and_conditions'],
            'proposed_dates' => $this->explodeInlineList($data['form']['proposed_dates']),
            'location_available_dates' => $this->explodeInlineList($data['form']['location_available_dates']),
            'scouting_status' => $data['form']['scouting_status'],
            'proposal_status' => $data['form']['proposal_status'],
            'notes' => $data['form']['notes'],
        ]);

        if ($proposal->proposal_status === CategoryBudgetSupplier::STATUS_REQUESTED) {
            $proposal->proposal_status = CategoryBudgetSupplier::STATUS_RECEIVED;
        }

        $proposal->save();

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

    public function openAcceptProposalModal(int $proposalId): void
    {
        $this->mountAction('acceptProposal', ['proposal' => $proposalId]);
    }

    public function getBudgetSummary(): array
    {
        $budget = $this->categoryBudgetRecord->loadMissing('category', 'supplierProposals.supplier');
        $confirmedProposal = $budget->confirmedProposal();
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
            'confirmed_supplier' => $confirmedProposal?->supplier?->name,
        ];
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

    public function getSupplierResults(): Collection
    {
        $filters = $this->supplierFilters;
        $trackedSupplierIds = $this->categoryBudgetRecord
            ->supplierProposals()
            ->pluck('supplier_id')
            ->filter()
            ->all();

        return Supplier::query()
            ->where('category_id', $this->categoryBudgetRecord->category_id)
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
            ->where('category_id', $this->categoryBudgetRecord->category_id)
            ->whereNotNull('service_area')
            ->where('service_area', '!=', '')
            ->orderBy('service_area')
            ->pluck('service_area', 'service_area')
            ->all();
    }

    public function getCityOptions(): array
    {
        return Supplier::query()
            ->where('category_id', $this->categoryBudgetRecord->category_id)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->orderBy('city')
            ->pluck('city', 'city')
            ->all();
    }

    public function getPriceRangeOptions(): array
    {
        return Supplier::query()
            ->where('category_id', $this->categoryBudgetRecord->category_id)
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
                $data['category_id'] = $this->categoryBudgetRecord->category_id;

                $supplier = Supplier::query()->create($data);

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
            ->requiresConfirmation()
            ->modalHeading('Accept this supplier quote?')
            ->modalDescription('The category budget will be marked as confirmed and the final amount will be updated from the selected proposal.')
            ->action(function (array $arguments): void {
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
                'supplierProposals.projectDocuments',
            ])
            ->firstOrFail();
    }

    protected function findProposalBySupplier(int $supplierId): ?CategoryBudgetSupplier
    {
        return $this->categoryBudgetRecord
            ->supplierProposals
            ->firstWhere('supplier_id', $supplierId);
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
}
