<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\CategoryBudgetSupplier;
use App\Models\Payment;
use App\Models\ProjectDocument;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class ViewProjectSuppliers extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-suppliers';

    protected static ?string $breadcrumb = 'Suppliers';

    protected Width|string|null $maxContentWidth = Width::Full;

    public bool $hidePaidPayments = false;

    public array $paymentCompletionForms = [];

    public array $openPaymentRegistrations = [];

    public array $paymentCompletionReceiptUploads = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return (string) $this->getRecordTitle();
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

    public function getSupplierProposals(): Collection
    {
        return $this->getRecord()
            ->categoryBudgetSuppliers()
            ->with([
                'category',
                'categoryBudget',
                'supplier',
                'communications',
                'payments.paymentReceiptDocument',
                'projectDocuments',
            ])
            ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED)
            ->get()
            ->sortBy(fn (CategoryBudgetSupplier $proposal): string => sprintf(
                '%05d-%s-%s',
                (int) ($proposal->category?->order ?? 99999),
                mb_strtolower((string) ($proposal->category?->label ?? 'zzz')),
                mb_strtolower((string) ($proposal->supplier?->name ?? 'zzz'))
            ))
            ->values();
    }

    public function getSuppliersSummary(): array
    {
        $proposals = $this->getSupplierProposals();

        return [
            'confirmed_count' => $proposals->count(),
            'contract_documents_count' => $proposals->sum(fn (CategoryBudgetSupplier $proposal): int => $proposal->projectDocuments->count()),
            'payments_total' => (float) $proposals->sum(fn (CategoryBudgetSupplier $proposal): float => (float) $proposal->payments->sum('amount')),
            'communications_count' => $proposals->sum(fn (CategoryBudgetSupplier $proposal): int => $proposal->communications->count()),
        ];
    }

    public function getProjectPayments(): Collection
    {
        return $this->getRecord()
            ->payments()
            ->with(['supplier', 'categoryBudgetSupplier.category', 'paymentReceiptDocument'])
            ->get()
            ->when(
                $this->hidePaidPayments,
                fn (Collection $payments): Collection => $payments
                    ->reject(fn (Payment $payment): bool => $payment->payment_status === Payment::STATUS_PAID)
                    ->values()
            )
            ->sortBy(fn (Payment $payment): string => sprintf(
                '%s-%05d',
                $payment->due_date?->format('Ymd') ?? '99999999',
                (int) $payment->id,
            ))
            ->values();
    }

    public function getPaymentsSummary(): array
    {
        $payments = $this->getRecord()->payments()->get();
        $unpaid = $payments->where('payment_status', '!=', Payment::STATUS_PAID);
        $today = now()->startOfDay();

        return [
            'total_count' => $payments->count(),
            'visible_count' => $this->getProjectPayments()->count(),
            'paid_count' => $payments->where('payment_status', Payment::STATUS_PAID)->count(),
            'unpaid_count' => $unpaid->count(),
            'overdue_count' => $unpaid
                ->filter(fn (Payment $payment): bool => $payment->due_date !== null && $payment->due_date->copy()->startOfDay()->lt($today))
                ->count(),
        ];
    }

    public function startPaymentRegistration(int $paymentId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $payment = $this->paymentForCurrentProject($paymentId);

        if ($payment->payment_status === Payment::STATUS_PAID) {
            return;
        }

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

        $payment = $this->paymentForCurrentProject($paymentId)->load('paymentReceiptDocument', 'categoryBudgetSupplier');

        $data = validator(
            [
                'form' => $this->paymentCompletionForms[$paymentId] ?? [],
                'receipt' => $this->paymentCompletionReceiptUploads[$paymentId] ?? null,
            ],
            [
                'form.paid_at' => ['required', 'date'],
                'receipt' => ['nullable', 'file', 'max:20480'],
            ],
        )->validate();

        $receiptDocumentId = $payment->payment_receipt_document_id;

        if (array_key_exists($paymentId, $this->paymentCompletionReceiptUploads) && $this->paymentCompletionReceiptUploads[$paymentId]) {
            if ($payment->paymentReceiptDocument) {
                Storage::disk('public')->delete($payment->paymentReceiptDocument->file_path);
                $payment->paymentReceiptDocument->delete();
            }

            $storedPath = $this->paymentCompletionReceiptUploads[$paymentId]->store('projects/payment-receipts', 'public');

            $receiptDocument = ProjectDocument::query()->create([
                'project_id' => $this->getRecord()->getKey(),
                'supplier_id' => $payment->supplier_id,
                'category_budget_supplier_id' => $payment->category_budget_supplier_id,
                'title' => 'Payment receipt - ' . ($payment->reason ?: 'Payment'),
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
        $this->record = $this->resolveRecord($this->getRecord()->getKey());

        Notification::make()
            ->title('Payment registered')
            ->success()
            ->send();
    }

    protected function paymentForCurrentProject(int $paymentId): Payment
    {
        return $this->getRecord()
            ->payments()
            ->whereKey($paymentId)
            ->firstOrFail();
    }
}
