<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Payment;
use App\Models\ProjectDocument;
use App\Models\ProjectImage;
use App\Models\ProjectSupplierCommunication;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
        'reason' => '',
        'amount' => '',
        'due_date' => '',
        'paid_at' => '',
        'invoice_reference' => '',
        'notes' => '',
    ];

    public string $paymentEntryMode = 'register';

    public $paymentReceiptUpload = null;

    public array $paymentCompletionForms = [];

    public array $openPaymentRegistrations = [];

    public array $paymentCompletionReceiptUploads = [];

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
        $this->proposalRecord = $this->resolveConfirmedProposal();
        $this->communicationForm['communication_at'] = now()->format('Y-m-d\TH:i');
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
            'documents_total' => $proposal->projectDocuments->count(),
            'images_total' => $this->getImages()->count(),
            'checklist_total' => count($this->getChecklistItems()),
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

    public function getChecklistItems(): array
    {
        return [
            ['title' => 'Review contract pack', 'detail' => 'Check quote, contract and signed contract availability.'],
            ['title' => 'Validate payment plan', 'detail' => 'Confirm deposits, balance and invoice references.'],
            ['title' => 'Update visual materials', 'detail' => 'Keep photogallery aligned with the latest setup references.'],
            ['title' => 'Confirm operational touchpoints', 'detail' => 'Track final logistics, contacts and timing checkpoints.'],
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

        return [
            [
                'key' => 'communications',
                'label' => 'Communications',
                'value' => $this->proposalRecord->communications->count(),
                'meta' => 'Timeline, follow-ups and supplier updates',
            ],
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
                'value' => count($this->getChecklistItems()),
                'meta' => 'Operational milestones and reminders',
            ],
        ];
    }

    public function setActiveWorkspaceTab(string $tab): void
    {
        if (! in_array($tab, ['communications', 'documents', 'photogallery', 'payments', 'checklist'], true)) {
            return;
        }

        $this->activeWorkspaceTab = $tab;
    }

    public function saveCommunication(): void
    {
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
        $data = validator(
            [
                'form' => $this->paymentForm,
                'receipt' => $this->paymentReceiptUpload,
            ],
            [
                'form.reason' => ['required', 'string', 'max:255'],
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
                'supplierProposals.payments.paymentReceiptDocument',
                'project.projectImages',
            ])
            ->firstOrFail();
    }

    protected function resolveConfirmedProposal(): CategoryBudgetSupplier
    {
        $proposal = $this->categoryBudgetRecord->supplierProposals
            ->firstWhere('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED);

        abort_if(! $proposal, 404);

        return $proposal;
    }

    protected function refreshContext(): void
    {
        $this->categoryBudgetRecord = $this->resolveCategoryBudget($this->categoryBudgetRecord->getKey());
        $this->record = $this->resolveRecord($this->getRecord()->getKey());
        $this->proposalRecord = $this->resolveConfirmedProposal();
    }

    protected function resetPaymentEntryForm(): void
    {
        $this->paymentForm = [
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
