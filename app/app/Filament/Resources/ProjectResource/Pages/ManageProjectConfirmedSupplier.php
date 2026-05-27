<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Checklist;
use App\Models\Payment;
use App\Models\PaymentMode;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use App\Models\ProjectDocument;
use App\Models\ProjectImage;
use App\Models\ProjectSupplierCommunication;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

class ManageProjectConfirmedSupplier extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.manage-project-confirmed-supplier';

    protected static ?string $breadcrumb = 'Manage supplier';

    protected Width|string|null $maxContentWidth = Width::Full;

    public CategoryBudget $categoryBudgetRecord;

    public CategoryBudgetSupplier $proposalRecord;

    public array $documentForm = [
        'type' => ProjectDocument::TYPE_CONTRACT,
        'title' => '',
        'description' => '',
    ];

    public $documentUpload = null;

    public array $communicationForm = [
        'communication_type' => 'email',
        'direction' => 'outgoing',
        'communication_at' => '',
        'subject' => '',
        'message' => '',
        'notes' => '',
    ];

    public array $paymentForm = [
        'payment_mode_id' => '',
        'reason' => '',
        'amount' => '',
        'due_date' => '',
        'paid_at' => '',
        'invoice_reference' => '',
        'notes' => '',
    ];

    public string $paymentEntryMode = 'register';

    public $paymentReceiptUpload = null;

    public array $commissionForm = [
        'commission_mode' => CategoryBudgetSupplier::COMMISSION_MODE_NONE,
        'commission_percentage' => '',
        'commission_amount' => '',
        'commission_total_amount_payed' => 0,
        'commission_payments_json' => [],
    ];

    public array $paymentCompletionForms = [];

    public array $openPaymentRegistrations = [];

    public array $paymentCompletionReceiptUploads = [];

    public array $checklistForms = [];

    public bool $hideCompleted = false;

    public ?int $expandedChecklistItemId = null;

    public ?int $confirmDeleteChecklistItemId = null;

    public ?int $pinnedChecklistItemId = null;

    public string $activeWorkspaceTab = 'communications';

    public array $imageForm = [
        'description' => '',
        'image_category' => 'other',
        'is_client_visible' => false,
    ];

    public $imageUpload = null;

    public function mount(int|string $record, int|string $categoryBudget): void
    {
        $this->record = $this->resolveRecord($record);

        $this->categoryBudgetRecord = $this->resolveCategoryBudget($categoryBudget);
        $this->proposalRecord = $this->resolveConfirmedProposal(request()->integer('proposal') ?: null);
        $this->communicationForm['communication_at'] = now()->format('Y-m-d\TH:i');

        if ($this->getRecord()->projectChecklistOptions()->doesntExist()) {
            $this->getRecord()->syncChecklistOptionsFromTemplates();
            $this->getRecord()->refresh();
        }

        $this->loadCommissionForm();
        $this->loadChecklistForms();

        if (auth()->user()?->isCustomer()) {
            $this->activeWorkspaceTab = 'documents';
        }
    }

    public function getTitle(): string|Htmlable
    {
        return (string) ($this->proposalRecord->supplier?->name ?: $this->getRecordTitle());
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

    public function getSummary(): array
    {
        $proposal = $this->proposalRecord->loadMissing('supplier', 'category', 'payments.paymentReceiptDocument', 'projectDocuments');
        $estimatedAmount = $this->categoryBudgetRecord->initial_estimated_amount !== null
            ? (float) $this->categoryBudgetRecord->initial_estimated_amount
            : null;
        $confirmedAmount = $proposal->proposed_amount !== null ? (float) $proposal->proposed_amount : null;
        $paidTotal = (float) $proposal->payments
            ->where('payment_status', Payment::STATUS_PAID)
            ->sum(fn (Payment $payment): float => (float) $payment->amount);
        $amountDelta = ($estimatedAmount !== null && $confirmedAmount !== null)
            ? $confirmedAmount - $estimatedAmount
            : null;

        return [
            'category' => $proposal->category?->label_it ?? 'Category',
            'supplier' => $proposal->supplier?->name ?? 'Supplier',
            'estimated_amount' => $estimatedAmount,
            'confirmed_amount' => $confirmedAmount,
            'amount_delta' => $amountDelta,
            'requested_at' => $proposal->requested_at,
            'responded_at' => $proposal->responded_at,
            'communications_total' => $proposal->communications->count(),
            'payments_total' => (float) $proposal->payments->sum(fn (Payment $payment): float => (float) $payment->amount),
            'payments_paid_total' => $paidTotal,
            'payments_paid_percentage' => $confirmedAmount && $confirmedAmount > 0
                ? ($paidTotal / $confirmedAmount) * 100
                : null,
            'commission_amount' => (float) ($proposal->commission_amount ?? 0),
            'commission_paid_total' => (float) ($proposal->commission_total_amount_payed ?? 0),
            'documents_total' => $proposal->projectDocuments->count(),
            'images_total' => $this->getImages()->count(),
            'checklist_total' => $this->getSupplierChecklistItems()->count(),
        ];
    }

    public function getCommunications(): Collection
    {
        return $this->proposalRecord
            ->communications
            ->sortByDesc(fn (ProjectSupplierCommunication $communication): int => $communication->communication_at?->timestamp ?? 0)
            ->values();
    }

    public function getDocumentsByType(string $type): Collection
    {
        return $this->proposalRecord
            ->projectDocuments
            ->where('type', $type)
            ->values();
    }

    public function getOtherDocuments(): Collection
    {
        return $this->proposalRecord
            ->projectDocuments
            ->whereNotIn('type', [
                ProjectDocument::TYPE_QUOTE,
                ProjectDocument::TYPE_CONTRACT,
                ProjectDocument::TYPE_SIGNED_CONTRACT,
                ProjectDocument::TYPE_INVOICE,
                ProjectDocument::TYPE_PAYMENT_RECEIPT,
            ])
            ->values();
    }

    public function getPayments(): Collection
    {
        return $this->proposalRecord
            ->payments
            ->sortBy([
                ['due_date', 'asc'],
                ['created_at', 'desc'],
            ])
            ->values();
    }

    public function getPaymentModeOptions(): array
    {
        $supplier = $this->proposalRecord->supplier;
        $acceptedIds = $supplier?->acceptedPaymentModeIds() ?? [];

        return PaymentMode::query()
            ->where('is_active', true)
            ->when(
                filled($acceptedIds),
                fn ($query) => $query->whereIn('id', $acceptedIds)
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getImages(): Collection
    {
        return $this->getRecord()
            ->projectImages
            ->where('supplier_id', $this->proposalRecord->supplier_id)
            ->values();
    }

    public function getDeadlinePlaceholders(): array
    {
        return [
            ['offset' => '10 months before', 'action' => 'Review supplier contract and payment schedule'],
            ['offset' => '6 months before', 'action' => 'Pay first deposit and request updated invoice'],
            ['offset' => '3 months before', 'action' => 'Confirm final scope, timings and logistics'],
            ['offset' => '30 days before', 'action' => 'Collect final balance instructions and latest documents'],
            ['offset' => '7 days before', 'action' => 'Share final contacts and event-day checklist'],
        ];
    }

    public function getCommissionSummary(): array
    {
        $amount = (float) ($this->proposalRecord->commission_amount ?? 0);
        $paid = (float) ($this->proposalRecord->commission_total_amount_payed ?? 0);

        return [
            'mode' => $this->proposalRecord->commission_mode ?? CategoryBudgetSupplier::COMMISSION_MODE_NONE,
            'mode_label' => CategoryBudgetSupplier::COMMISSION_MODE_OPTIONS[$this->proposalRecord->commission_mode ?? CategoryBudgetSupplier::COMMISSION_MODE_NONE] ?? 'None',
            'percentage' => $this->proposalRecord->commission_percentage !== null ? (float) $this->proposalRecord->commission_percentage : null,
            'amount' => $amount,
            'paid' => $paid,
            'balance' => max(0, $amount - $paid),
            'payments' => collect($this->proposalRecord->commission_payments_json ?? []),
        ];
    }

    public function getDashboardCards(): array
    {
        $quoteCount = $this->getDocumentsByType(ProjectDocument::TYPE_QUOTE)->count();
        $unpaidPayments = $this->getPayments()->where('payment_status', Payment::STATUS_UNPAID)->count();
        $nextPayment = $this->getPayments()
            ->where('payment_status', Payment::STATUS_UNPAID)
            ->filter(fn (Payment $payment): bool => $payment->due_date !== null)
            ->sortBy('due_date')
            ->first();

        $cards = [
            [
                'key' => 'documents',
                'label' => 'Documents',
                'value' => $this->proposalRecord->projectDocuments->count(),
                'meta' => $quoteCount . ' quote files · ' . $this->getDocumentsByType(ProjectDocument::TYPE_CONTRACT)->count() . ' contracts',
            ],
            [
                'key' => 'photogallery',
                'label' => 'Photogallery',
                'value' => $this->getImages()->count(),
                'meta' => 'Visual references and client-facing materials',
            ],
            [
                'key' => 'payments',
                'label' => 'Payments',
                'value' => 'EUR ' . number_format((float) $this->proposalRecord->payments->sum('amount'), 2, ',', '.'),
                'meta' => $unpaidPayments . ' unpaid' . ($nextPayment?->due_date ? ' · next ' . $nextPayment->due_date->format('d/m/Y') : ''),
            ],
            [
                'key' => 'checklist',
                'label' => 'Checklist',
                'value' => $this->getSupplierChecklistItems()->count(),
                'meta' => 'Tasks linked to this supplier',
            ],
            [
                'key' => 'communications',
                'label' => 'Communications',
                'value' => $this->proposalRecord->communications->count(),
                'meta' => 'Timeline, follow-ups and supplier updates',
                'footer' => 'Not visible to clients',
            ],
        ];

        if (! auth()->user()?->isCustomer()) {
            $commissionAmount = (float) ($this->proposalRecord->commission_amount ?? 0);
            $commissionPaid = (float) ($this->proposalRecord->commission_total_amount_payed ?? 0);

            $cards[] = [
                'key' => 'commissions',
                'label' => 'Commissions',
                'value' => 'EUR ' . number_format($commissionAmount, 2, ',', '.'),
                'meta' => 'Paid EUR ' . number_format($commissionPaid, 2, ',', '.') . ' · balance EUR ' . number_format(max(0, $commissionAmount - $commissionPaid), 2, ',', '.'),
                'footer' => 'Not visible to clients',
            ];
        }

        if (auth()->user()?->isCustomer()) {
            return collect($cards)
                ->reject(fn (array $card): bool => in_array($card['key'], ['communications', 'commissions'], true))
                ->values()
                ->all();
        }

        return $cards;
    }

    public function setActiveWorkspaceTab(string $tab): void
    {
        if (auth()->user()?->isCustomer() && in_array($tab, ['communications', 'commissions'], true)) {
            return;
        }

        if (! in_array($tab, ['communications', 'documents', 'photogallery', 'payments', 'checklist', 'commissions'], true)) {
            return;
        }

        $this->activeWorkspaceTab = $tab;
    }

    public function updatedCommissionForm(): void
    {
        $this->syncCommissionFormCalculatedValues();
    }

    public function addCommissionPayment(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $this->commissionForm['commission_payments_json'][] = [
            'invoice_date' => '',
            'due_date' => '',
            'amount' => '',
            'paid_at' => '',
        ];

        $this->syncCommissionFormCalculatedValues();
    }

    public function removeCommissionPayment(int $index): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $payments = $this->commissionForm['commission_payments_json'] ?? [];

        if (! array_key_exists($index, $payments)) {
            return;
        }

        unset($payments[$index]);
        $this->commissionForm['commission_payments_json'] = array_values($payments);
        $this->syncCommissionFormCalculatedValues();
    }

    public function saveCommission(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $this->syncCommissionFormCalculatedValues();

        $data = validator(
            ['form' => $this->commissionForm],
            [
                'form.commission_mode' => ['required', Rule::in(array_keys(CategoryBudgetSupplier::COMMISSION_MODE_OPTIONS))],
                'form.commission_percentage' => [
                    Rule::requiredIf(($this->commissionForm['commission_mode'] ?? null) === CategoryBudgetSupplier::COMMISSION_MODE_PERCENTAGE),
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100',
                ],
                'form.commission_amount' => [
                    Rule::requiredIf(($this->commissionForm['commission_mode'] ?? null) === CategoryBudgetSupplier::COMMISSION_MODE_FIXED),
                    'nullable',
                    'numeric',
                    'min:0',
                ],
                'form.commission_payments_json' => ['array'],
                'form.commission_payments_json.*.invoice_date' => ['nullable', 'date'],
                'form.commission_payments_json.*.due_date' => ['nullable', 'date'],
                'form.commission_payments_json.*.amount' => ['nullable', 'numeric', 'min:0'],
                'form.commission_payments_json.*.paid_at' => ['nullable', 'date'],
            ]
        )->validate();

        $payments = collect($data['form']['commission_payments_json'] ?? [])
            ->filter(fn (array $payment): bool => filled($payment['invoice_date'] ?? null)
                || filled($payment['due_date'] ?? null)
                || filled($payment['amount'] ?? null)
                || filled($payment['paid_at'] ?? null))
            ->map(fn (array $payment): array => [
                'invoice_date' => filled($payment['invoice_date'] ?? null) ? $payment['invoice_date'] : null,
                'due_date' => filled($payment['due_date'] ?? null) ? $payment['due_date'] : null,
                'amount' => round(max(0, (float) ($payment['amount'] ?? 0)), 2),
                'paid_at' => filled($payment['paid_at'] ?? null) ? $payment['paid_at'] : null,
            ])
            ->values()
            ->all();

        $this->proposalRecord->fill([
            'commission_mode' => $data['form']['commission_mode'],
            'commission_percentage' => $data['form']['commission_mode'] === CategoryBudgetSupplier::COMMISSION_MODE_PERCENTAGE
                ? $data['form']['commission_percentage']
                : null,
            'commission_amount' => $data['form']['commission_amount'] ?? 0,
            'commission_payments_json' => $payments,
        ]);

        $this->proposalRecord->normalizeCommissionFields();
        $this->proposalRecord->save();

        $this->refreshContext();
        $this->loadCommissionForm();

        Notification::make()
            ->title('Commissions saved')
            ->success()
            ->send();
    }

    public function getChecklistSummary(): array
    {
        $items = $this->getSupplierChecklistItems();
        $today = now()->startOfDay();
        $dueSoonLimit = now()->addDays(30)->startOfDay();

        return [
            'sections' => $this->getChecklistSections()->count(),
            'total' => $items->count(),
            'completed' => $items->where('completed', true)->count(),
            'open' => $items->where('completed', false)->count(),
            'due_soon' => $items
                ->where('completed', false)
                ->filter(fn (ProjectChecklistOption $item): bool => $item->due_date !== null && $item->due_date->between($today, $dueSoonLimit))
                ->count(),
        ];
    }

    public function getChecklistSections(): Collection
    {
        $items = $this->getSupplierChecklistItems();
        $record = $this->getRecord();
        $clientLabel = collect([$record->partner_one_name, $record->partner_two_name])->filter()->implode(' & ');
        $supplierName = $this->proposalRecord->supplier?->name ?? 'Supplier';
        $supplierSubtitle = $this->proposalRecord->supplier?->category?->label_it ?? ($this->proposalRecord->supplier?->category?->label ?? 'supplier');

        $sections = collect([
            [
                'key' => 'admin',
                'title' => 'ME',
                'subtitle' => 'planner',
                'avatar' => 'GB',
                'items' => $items->where('assigned_to', 'admin')->values(),
            ],
            [
                'key' => 'client',
                'title' => $clientLabel !== '' ? mb_strtoupper($clientLabel) : 'CLIENT',
                'subtitle' => 'client',
                'avatar' => $this->getInitials($clientLabel !== '' ? $clientLabel : 'Client'),
                'items' => $items->where('assigned_to', 'client')->values(),
            ],
            [
                'key' => 'supplier-' . ($this->proposalRecord->supplier_id ?? 'unassigned'),
                'title' => mb_strtoupper($supplierName),
                'subtitle' => $supplierSubtitle,
                'avatar' => $this->getInitials($supplierName),
                'items' => $items->where('assigned_to', 'supplier')->values(),
            ],
        ]);

        if (auth()->user()?->isCustomer()) {
            $sections = $sections
                ->reject(fn (array $section): bool => $section['key'] === 'admin')
                ->values();
        }

        return $sections
            ->map(function (array $section): array {
                /** @var Collection<int, ProjectChecklistOption> $sectionItems */
                $sectionItems = $section['items'];
                $visibleItems = $this->hideCompleted
                    ? $sectionItems->where('completed', false)->values()
                    : $sectionItems->values();

                return [
                    ...$section,
                    'items' => $visibleItems->sortBy(fn (ProjectChecklistOption $item): string => sprintf(
                        '%d-%s-%05d',
                        $this->pinnedChecklistItemId === $item->id ? 0 : 1,
                        $item->due_date?->format('Ymd') ?? '99999999',
                        $item->order,
                    ))->values(),
                    'count' => $visibleItems->count(),
                    'total_count' => $sectionItems->count(),
                ];
            });
    }

    public function saveChecklistItem(int $itemId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $item = $this->findChecklistItem($itemId);
        $data = $this->checklistForms[$itemId] ?? [];

        validator($data, [
            'title' => ['nullable', 'string'],
            'details' => ['nullable', 'string'],
            'to_be_filled' => ['nullable', 'boolean'],
            'supplier_id' => ['nullable', 'integer', Rule::in(array_keys($this->getSupplierOptions()))],
        ])->validate();

        $item->forceFill([
            'title' => trim((string) ($data['title'] ?? '')),
            'details' => filled($data['details'] ?? null) ? trim((string) $data['details']) : null,
            'to_be_filled' => (bool) ($data['to_be_filled'] ?? false),
            'supplier_id' => filled($data['supplier_id'] ?? null) ? (int) $data['supplier_id'] : null,
        ])->save();

        $this->syncChecklistForm($item->fresh());
    }

    public function updatedChecklistForms(mixed $value, string $name): void
    {
        if (! preg_match('/^(\d+)\.(title|details|to_be_filled|supplier_id|response|anticipation_value|anticipation_unit|exact_due_date)$/', $name, $matches)) {
            return;
        }

        if ($matches[2] === 'response') {
            $this->saveChecklistResponse((int) $matches[1]);

            return;
        }

        if (auth()->user()?->isCustomer()) {
            return;
        }

        if (in_array($matches[2], ['title', 'details', 'to_be_filled', 'supplier_id'], true)) {
            $this->saveChecklistItem((int) $matches[1]);

            return;
        }

        $this->saveChecklistSchedule((int) $matches[1]);
    }

    public function saveChecklistResponse(int $itemId): void
    {
        $item = $this->findChecklistItem($itemId);
        $data = $this->checklistForms[$itemId] ?? [];

        validator($data, [
            'response' => ['nullable', 'string'],
        ])->validate();

        $item->forceFill([
            'response' => filled($data['response'] ?? null) ? trim((string) $data['response']) : null,
        ])->save();

        $this->syncChecklistForm($item->fresh());
    }

    public function toggleChecklistCompleted(int $itemId, bool $completed): void
    {
        $item = $this->findChecklistItem($itemId);
        $data = $this->checklistForms[$itemId] ?? [];
        $response = filled($data['response'] ?? null)
            ? trim((string) $data['response'])
            : null;

        if ($completed && $item->to_be_filled && blank($response)) {
            Notification::make()
                ->title('A response is required before completing this task')
                ->warning()
                ->send();

            $this->syncChecklistForm($item->fresh());

            return;
        }

        $item->forceFill([
            'response' => $item->to_be_filled ? $response : $item->response,
            'completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ])->save();

        $this->syncChecklistForm($item->fresh());
    }

    public function addChecklistItem(string $assignedTo, ?int $supplierId = null): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $assignedTo = ProjectChecklistOption::normalizeAssignedTo($assignedTo);
        $supplierId = $supplierId ?: $this->proposalRecord->supplier_id;

        $customChecklist = Checklist::query()->firstOrCreate(
            ['title' => 'Custom checklist'],
            ['category_id' => null, 'options' => []],
        );

        $nextOrder = ((int) $this->getRecord()->projectChecklistOptions()
            ->where('checkbox_id', $customChecklist->id)
            ->max('order')) + 1;

        $item = $this->getRecord()->projectChecklistOptions()->create([
            'supplier_id' => $supplierId,
            'category_budget_id' => $this->categoryBudgetRecord->getKey(),
            'checkbox_id' => $customChecklist->id,
            'order' => $nextOrder > 0 ? $nextOrder : 1,
            'title' => '',
            'details' => null,
            'response' => null,
            'default' => false,
            'to_be_filled' => false,
            'anticipation' => null,
            'assigned_to' => $assignedTo,
            'due_date' => null,
            'enabled' => true,
            'completed' => false,
            'completed_at' => null,
        ]);

        $this->reloadChecklistState();
        $this->syncChecklistForm($item->fresh());
        $this->expandedChecklistItemId = $item->id;
        $this->pinnedChecklistItemId = $item->id;
    }

    public function saveChecklistSchedule(int $itemId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $item = $this->findChecklistItem($itemId);
        $data = $this->checklistForms[$itemId] ?? [];
        $mode = $data['due_date_mode'] ?? 'relative';

        if ($mode === 'exact') {
            $exactDueDate = filled($data['exact_due_date'] ?? null)
                ? Carbon::parse((string) $data['exact_due_date'])->startOfDay()
                : null;

            $item->forceFill([
                'anticipation' => null,
                'due_date' => $exactDueDate,
            ])->save();

            $this->syncChecklistForm($item->fresh());

            return;
        }

        $value = isset($data['anticipation_value']) && $data['anticipation_value'] !== ''
            ? (int) $data['anticipation_value']
            : null;
        $unit = in_array(($data['anticipation_unit'] ?? null), ['days', 'weeks', 'months'], true)
            ? (string) $data['anticipation_unit']
            : null;

        $anticipation = ($value && $value > 0 && $unit)
            ? $value . ' ' . $unit
            : null;

        $item->forceFill([
            'anticipation' => $anticipation,
            'due_date' => Project::calculateChecklistDueDate($this->getRecord()->event_start_date, $anticipation),
        ])->save();

        $this->syncChecklistForm($item->fresh());
    }

    public function promptDeleteChecklistItem(int $itemId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $item = $this->findChecklistItem($itemId);

        $this->confirmDeleteChecklistItemId = $item->id;
    }

    public function cancelDeleteChecklistItem(): void
    {
        $this->confirmDeleteChecklistItemId = null;
    }

    public function confirmDeleteChecklistItem(): void
    {
        if (! $this->confirmDeleteChecklistItemId) {
            return;
        }

        $this->deleteChecklistItem($this->confirmDeleteChecklistItemId);
    }

    public function deleteChecklistItem(int $itemId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $item = $this->findChecklistItem($itemId);

        unset($this->checklistForms[$itemId]);
        $item->delete();

        if ($this->expandedChecklistItemId === $itemId) {
            $this->expandedChecklistItemId = null;
        }

        $this->confirmDeleteChecklistItemId = null;
        $this->reloadChecklistState();

        Notification::make()
            ->title('Task deleted')
            ->success()
            ->send();
    }

    public function expandChecklistItem(int $itemId): void
    {
        $this->expandedChecklistItemId = $itemId;
    }

    public function collapseChecklistItem(): void
    {
        if ($this->expandedChecklistItemId && ! auth()->user()?->isCustomer()) {
            $this->saveChecklistSchedule($this->expandedChecklistItemId);
        }

        if ($this->pinnedChecklistItemId === $this->expandedChecklistItemId) {
            $this->pinnedChecklistItemId = null;
        }

        $this->expandedChecklistItemId = null;
    }

    public function saveCommunication(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $data = validator(
            ['form' => $this->communicationForm],
            [
                'form.communication_type' => ['required', 'string'],
                'form.direction' => ['required', 'string'],
                'form.communication_at' => ['required', 'date'],
                'form.subject' => ['nullable', 'string', 'max:255'],
                'form.message' => ['nullable', 'string'],
                'form.notes' => ['nullable', 'string'],
            ]
        )->validate();

        $this->proposalRecord->communications()->create([
            'project_id' => $this->getRecord()->getKey(),
            'supplier_id' => $this->proposalRecord->supplier_id,
            'communication_type' => $data['form']['communication_type'],
            'direction' => $data['form']['direction'],
            'communication_at' => $data['form']['communication_at'],
            'subject' => $data['form']['subject'] ?: null,
            'message' => $data['form']['message'] ?: null,
            'notes' => $data['form']['notes'] ?: null,
        ]);

        $this->communicationForm = [
            'communication_type' => 'email',
            'direction' => 'outgoing',
            'communication_at' => now()->format('Y-m-d\TH:i'),
            'subject' => '',
            'message' => '',
            'notes' => '',
        ];

        $this->refreshContext();

        Notification::make()
            ->title('Communication saved')
            ->success()
            ->send();
    }

    public function saveDocument(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $data = validator(
            [
                'form' => $this->documentForm,
                'upload' => $this->documentUpload,
            ],
            [
                'form.type' => ['required', 'string'],
                'form.title' => ['nullable', 'string', 'max:255'],
                'form.description' => ['nullable', 'string'],
                'upload' => ['required', 'file', 'max:20480'],
            ]
        )->validate();

        $storedPath = $this->documentUpload->store('projects/documents', 'public');
        $type = (string) $data['form']['type'];

        $this->proposalRecord->projectDocuments()->create([
            'project_id' => $this->getRecord()->getKey(),
            'supplier_id' => $this->proposalRecord->supplier_id,
            'title' => $data['form']['title'] ?: (ProjectDocument::TYPE_OPTIONS[$type] ?? 'Document'),
            'document_type' => $type,
            'type' => $type,
            'file_path' => $storedPath,
            'description' => $data['form']['description'] ?? null,
        ]);

        $this->documentForm = [
            'type' => ProjectDocument::TYPE_CONTRACT,
            'title' => '',
            'description' => '',
        ];
        $this->documentUpload = null;

        $this->refreshContext();

        Notification::make()
            ->title('Document saved')
            ->success()
            ->send();
    }

    public function deleteDocument(int $documentId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $document = $this->proposalRecord->projectDocuments()->whereKey($documentId)->firstOrFail();

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        $this->refreshContext();

        Notification::make()
            ->title('Document deleted')
            ->success()
            ->send();
    }

    public function savePayment(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $data = validator(
            [
                'form' => $this->paymentForm,
                'receipt' => $this->paymentReceiptUpload,
            ],
            [
                'form.reason' => ['required', 'string', 'max:255'],
                'form.payment_mode_id' => ['required', 'integer'],
                'form.amount' => ['required', 'numeric'],
                'form.due_date' => ['nullable', 'date'],
                'form.paid_at' => ['nullable', 'date'],
                'form.invoice_reference' => ['nullable', 'string', 'max:255'],
                'form.notes' => ['nullable', 'string'],
                'receipt' => ['nullable', 'file', 'max:20480'],
            ]
        )->validate();

        $paymentStatus = $this->paymentEntryMode === 'register'
            ? Payment::STATUS_PAID
            : Payment::STATUS_UNPAID;
        $paymentModeId = (int) $data['form']['payment_mode_id'];
        $allowedPaymentModeIds = array_map('intval', array_keys($this->getPaymentModeOptions()));

        if ($paymentModeId && ! in_array($paymentModeId, $allowedPaymentModeIds, true)) {
            Notification::make()
                ->title('This payment mode is not accepted by the supplier')
                ->danger()
                ->send();

            return;
        }

        $receiptDocumentId = null;

        if ($this->paymentEntryMode === 'register' && $this->paymentReceiptUpload) {
            $storedPath = $this->paymentReceiptUpload->store('projects/payment-receipts', 'public');

            $receiptDocument = $this->proposalRecord->projectDocuments()->create([
                'project_id' => $this->getRecord()->getKey(),
                'supplier_id' => $this->proposalRecord->supplier_id,
                'title' => 'Payment receipt - ' . $data['form']['reason'],
                'document_type' => ProjectDocument::TYPE_PAYMENT_RECEIPT,
                'type' => ProjectDocument::TYPE_PAYMENT_RECEIPT,
                'file_path' => $storedPath,
                'description' => $data['form']['notes'] ?? null,
            ]);

            $receiptDocumentId = $receiptDocument->id;
        }

        $this->proposalRecord->payments()->create([
            'project_id' => $this->getRecord()->getKey(),
            'supplier_id' => $this->proposalRecord->supplier_id,
            'payment_mode_id' => $paymentModeId,
            'reason' => $data['form']['reason'],
            'amount' => $data['form']['amount'],
            'due_date' => $data['form']['due_date'] ?: null,
            'payment_status' => $paymentStatus,
            'paid_at' => $paymentStatus === Payment::STATUS_PAID ? ($data['form']['paid_at'] ?: now()->toDateString()) : null,
            'invoice_reference' => $data['form']['invoice_reference'] ?: null,
            'payment_receipt_document_id' => $receiptDocumentId,
            'notes' => $data['form']['notes'] ?: null,
        ]);

        $this->resetPaymentEntryForm();

        $this->refreshContext();

        Notification::make()
            ->title($paymentStatus === Payment::STATUS_PAID ? 'Payment registered' : 'Payment scheduled')
            ->success()
            ->send();
    }

    public function startPaymentRegistration(int $paymentId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $payment = $this->proposalRecord->payments()->whereKey($paymentId)->firstOrFail();

        $this->paymentCompletionForms[$paymentId] = [
            'paid_at' => $payment->paid_at?->toDateString() ?? now()->toDateString(),
        ];
        $this->openPaymentRegistrations[$paymentId] = true;
    }

    public function cancelPaymentRegistration(int $paymentId): void
    {
        unset(
            $this->paymentCompletionForms[$paymentId],
            $this->openPaymentRegistrations[$paymentId],
            $this->paymentCompletionReceiptUploads[$paymentId],
        );
    }

    public function registerScheduledPayment(int $paymentId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $payment = $this->proposalRecord->payments()->with('paymentReceiptDocument')->whereKey($paymentId)->firstOrFail();

        $data = validator(
            [
                'form' => $this->paymentCompletionForms[$paymentId] ?? [],
                'receipt' => $this->paymentCompletionReceiptUploads[$paymentId] ?? null,
            ],
            [
                'form.paid_at' => ['required', 'date'],
                'receipt' => ['nullable', 'file', 'max:20480'],
            ]
        )->validate();

        $receiptDocumentId = $payment->payment_receipt_document_id;

        if (array_key_exists($paymentId, $this->paymentCompletionReceiptUploads) && $this->paymentCompletionReceiptUploads[$paymentId]) {
            if ($payment->paymentReceiptDocument) {
                Storage::disk('public')->delete($payment->paymentReceiptDocument->file_path);
                $payment->paymentReceiptDocument->delete();
            }

            $storedPath = $this->paymentCompletionReceiptUploads[$paymentId]->store('projects/payment-receipts', 'public');

            $receiptDocument = $this->proposalRecord->projectDocuments()->create([
                'project_id' => $this->getRecord()->getKey(),
                'supplier_id' => $this->proposalRecord->supplier_id,
                'title' => 'Payment receipt - ' . $payment->reason,
                'document_type' => ProjectDocument::TYPE_PAYMENT_RECEIPT,
                'type' => ProjectDocument::TYPE_PAYMENT_RECEIPT,
                'file_path' => $storedPath,
                'description' => $payment->notes,
            ]);

            $receiptDocumentId = $receiptDocument->id;
        }

        $payment->update([
            'payment_status' => Payment::STATUS_PAID,
            'paid_at' => $data['form']['paid_at'],
            'payment_receipt_document_id' => $receiptDocumentId,
        ]);

        $this->cancelPaymentRegistration($paymentId);
        $this->refreshContext();

        Notification::make()
            ->title('Payment registered')
            ->success()
            ->send();
    }

    public function updatedPaymentEntryMode(string $value): void
    {
        if ($value === 'schedule') {
            $this->paymentForm['paid_at'] = '';
            $this->paymentReceiptUpload = null;
        }
    }

    public function deletePayment(int $paymentId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $payment = $this->proposalRecord->payments()->with('paymentReceiptDocument')->whereKey($paymentId)->firstOrFail();

        if ($payment->paymentReceiptDocument) {
            Storage::disk('public')->delete($payment->paymentReceiptDocument->file_path);
            $payment->paymentReceiptDocument->delete();
        }

        $payment->delete();

        $this->refreshContext();

        Notification::make()
            ->title('Payment deleted')
            ->success()
            ->send();
    }

    public function saveImage(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $data = validator(
            [
                'form' => $this->imageForm,
                'upload' => $this->imageUpload,
            ],
            [
                'form.description' => ['nullable', 'string'],
                'form.image_category' => ['required', 'string'],
                'form.is_client_visible' => ['boolean'],
                'upload' => ['required', 'image', 'max:20480'],
            ]
        )->validate();

        $storedPath = $this->imageUpload->store('projects/images', 'public');

        $this->getRecord()->projectImages()->create([
            'supplier_id' => $this->proposalRecord->supplier_id,
            'image_path' => $storedPath,
            'description' => $data['form']['description'] ?? null,
            'image_category' => $data['form']['image_category'],
            'is_client_visible' => (bool) ($data['form']['is_client_visible'] ?? false),
        ]);

        $this->imageForm = [
            'description' => '',
            'image_category' => 'other',
            'is_client_visible' => false,
        ];
        $this->imageUpload = null;

        $this->refreshContext();

        Notification::make()
            ->title('Image saved')
            ->success()
            ->send();
    }

    public function deleteImage(int $imageId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $image = $this->getRecord()->projectImages()->where('supplier_id', $this->proposalRecord->supplier_id)->whereKey($imageId)->firstOrFail();

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        $this->refreshContext();

        Notification::make()
            ->title('Image deleted')
            ->success()
            ->send();
    }

    protected function resolveCategoryBudget(int|string $categoryBudget): CategoryBudget
    {
        return CategoryBudget::query()
            ->where('project_id', $this->getRecord()->getKey())
            ->whereKey($categoryBudget)
            ->with([
                'category',
                'supplierProposals.supplier',
                'supplierProposals.category',
                'supplierProposals.projectDocuments',
                'supplierProposals.communications',
                'supplierProposals.payments.paymentMode',
                'supplierProposals.payments.paymentReceiptDocument',
                'project.projectImages',
            ])
            ->firstOrFail();
    }

    protected function resolveConfirmedProposal(?int $proposalId = null): CategoryBudgetSupplier
    {
        $availableProposals = $this->categoryBudgetRecord->supplierProposals
            ->when(
                auth()->user()?->isCustomer(),
                fn (Collection $proposals): Collection => $proposals->where('scouting_status', 'shortlist'),
                fn (Collection $proposals): Collection => $proposals->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED),
            );

        $proposal = $proposalId
            ? $availableProposals->firstWhere('id', $proposalId)
            : $availableProposals->first();

        abort_if(! $proposal, 404);

        return $proposal;
    }

    protected function refreshContext(): void
    {
        $this->categoryBudgetRecord = $this->resolveCategoryBudget($this->categoryBudgetRecord->getKey());
        $this->record = $this->resolveRecord($this->getRecord()->getKey());
        $this->proposalRecord = $this->resolveConfirmedProposal($this->proposalRecord->getKey());
    }

    protected function loadCommissionForm(): void
    {
        $this->commissionForm = [
            'commission_mode' => $this->proposalRecord->commission_mode ?? CategoryBudgetSupplier::COMMISSION_MODE_NONE,
            'commission_percentage' => $this->proposalRecord->commission_percentage !== null ? (string) $this->proposalRecord->commission_percentage : '',
            'commission_amount' => $this->proposalRecord->commission_amount !== null ? (string) $this->proposalRecord->commission_amount : '',
            'commission_total_amount_payed' => (float) ($this->proposalRecord->commission_total_amount_payed ?? 0),
            'commission_payments_json' => collect($this->proposalRecord->commission_payments_json ?? [])
                ->map(fn (array $payment): array => [
                    'invoice_date' => $payment['invoice_date'] ?? '',
                    'due_date' => $payment['due_date'] ?? '',
                    'amount' => isset($payment['amount']) ? (string) $payment['amount'] : '',
                    'paid_at' => $payment['paid_at'] ?? '',
                ])
                ->values()
                ->all(),
        ];

        $this->syncCommissionFormCalculatedValues();
    }

    protected function getSupplierChecklistItems(): Collection
    {
        return $this->getRecord()
            ->loadMissing([
                'projectChecklistOptions.checklist.category',
                'projectChecklistOptions.supplier.category',
            ])
            ->projectChecklistOptions
            ->where('enabled', true)
            ->where('supplier_id', $this->proposalRecord->supplier_id)
            ->values();
    }

    public function getSupplierOptions(): array
    {
        return $this->getRecord()
            ->loadMissing('categoryBudgetSuppliers.supplier')
            ->categoryBudgetSuppliers
            ->map(fn ($proposal) => $proposal->supplier)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->mapWithKeys(fn ($supplier): array => [$supplier->id => $supplier->name])
            ->all();
    }

    protected function findChecklistItem(int $itemId): ProjectChecklistOption
    {
        /** @var ProjectChecklistOption $item */
        $item = $this->getRecord()
            ->projectChecklistOptions()
            ->where('supplier_id', $this->proposalRecord->supplier_id)
            ->with(['checklist', 'supplier.category'])
            ->findOrFail($itemId);

        return $item;
    }

    protected function reloadChecklistState(): void
    {
        $this->getRecord()->unsetRelation('projectChecklistOptions');
        $this->getRecord()->refresh();
        $this->loadChecklistForms();
    }

    protected function loadChecklistForms(): void
    {
        $this->checklistForms = $this->getRecord()
            ->projectChecklistOptions()
            ->where('supplier_id', $this->proposalRecord->supplier_id)
            ->get()
            ->mapWithKeys(fn (ProjectChecklistOption $item): array => [$item->id => $this->makeChecklistFormState($item)])
            ->all();
    }

    protected function syncChecklistForm(ProjectChecklistOption $item): void
    {
        $this->checklistForms[$item->id] = $this->makeChecklistFormState($item);

        $this->getRecord()->unsetRelation('projectChecklistOptions');
    }

    protected function makeChecklistFormState(ProjectChecklistOption $item): array
    {
        [$anticipationValue, $anticipationUnit] = $this->splitAnticipation($item->anticipation);

        return [
            'title' => $item->title ?? '',
            'details' => $item->details ?? '',
            'response' => $item->response ?? '',
            'supplier_id' => $item->supplier_id ?? '',
            'completed' => (bool) $item->completed,
            'to_be_filled' => (bool) $item->to_be_filled,
            'due_date_mode' => $item->anticipation ? 'relative' : 'exact',
            'anticipation_value' => $anticipationValue,
            'anticipation_unit' => $anticipationUnit ?? 'weeks',
            'exact_due_date' => $item->due_date?->format('Y-m-d') ?? '',
        ];
    }

    protected function splitAnticipation(?string $anticipation): array
    {
        if (! $anticipation) {
            return ['', null];
        }

        $parts = preg_split('/\s+/', trim($anticipation), 2);

        if (! is_array($parts) || count($parts) < 2) {
            return ['', null];
        }

        return [
            is_numeric($parts[0]) ? (string) ((int) $parts[0]) : '',
            trim((string) $parts[1]),
        ];
    }

    protected function getInitials(string $label): string
    {
        $parts = collect(preg_split('/\s+/', trim($label)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)));

        return $parts->implode('') ?: 'GB';
    }

    protected function syncCommissionFormCalculatedValues(): void
    {
        $mode = $this->commissionForm['commission_mode'] ?? CategoryBudgetSupplier::COMMISSION_MODE_NONE;

        if ($mode === CategoryBudgetSupplier::COMMISSION_MODE_PERCENTAGE) {
            $percentage = max(0, min(100, (float) ($this->commissionForm['commission_percentage'] ?? 0)));
            $this->commissionForm['commission_amount'] = (string) round(((float) ($this->proposalRecord->proposed_amount ?? 0)) * ($percentage / 100), 2);
        } elseif ($mode === CategoryBudgetSupplier::COMMISSION_MODE_NONE) {
            $this->commissionForm['commission_percentage'] = '';
            $this->commissionForm['commission_amount'] = '0';
        } elseif ($mode === CategoryBudgetSupplier::COMMISSION_MODE_FIXED) {
            $this->commissionForm['commission_percentage'] = '';
        }

        $this->commissionForm['commission_total_amount_payed'] = CategoryBudgetSupplier::calculateCommissionPaidTotal(
            collect($this->commissionForm['commission_payments_json'] ?? [])
                ->filter(fn ($payment): bool => is_array($payment))
                ->map(fn (array $payment): array => [
                    'amount' => $payment['amount'] ?? 0,
                    'paid_at' => $payment['paid_at'] ?? null,
                ])
                ->values()
                ->all()
        );
    }

    protected function resetPaymentEntryForm(): void
    {
        $this->paymentForm = [
            'payment_mode_id' => '',
            'reason' => '',
            'amount' => '',
            'due_date' => '',
            'paid_at' => '',
            'invoice_reference' => '',
            'notes' => '',
        ];
        $this->paymentReceiptUpload = null;
        $this->paymentEntryMode = 'register';
    }
}
