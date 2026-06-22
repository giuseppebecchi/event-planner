<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\CategoryBudgetSupplier;
use App\Models\Payment;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ViewProjectSuppliers extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-suppliers';

    protected static ?string $breadcrumb = 'Suppliers';

    protected Width|string|null $maxContentWidth = Width::Full;

    public bool $hidePaidPayments = false;

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
            ->with(['supplier', 'categoryBudgetSupplier.category'])
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
}
