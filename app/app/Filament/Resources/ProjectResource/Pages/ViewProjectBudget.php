<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\CategoryBudget;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ViewProjectBudget extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-budget';

    protected static ?string $breadcrumb = 'Budget';

    protected Width|string|null $maxContentWidth = Width::Full;

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

    public function getBudgetSummary(): array
    {
        $project = $this->getRecord()->loadMissing('categoryBudgets');
        $budgets = $project->categoryBudgets;
        $confirmed = $budgets->where('budget_status', CategoryBudget::STATUS_CONFIRMED);
        $inEvaluation = $budgets->where('budget_status', CategoryBudget::STATUS_IN_EVALUATION);

        $estimatedTotal = (float) $budgets->sum(fn (CategoryBudget $budget) => (float) ($budget->initial_estimated_amount ?? 0));
        $comparisonTotal = (float) $budgets->sum(fn (CategoryBudget $budget) => (float) ($budget->comparison_amount ?? $budget->initial_estimated_amount ?? 0));
        $finalTotal = (float) $confirmed->sum(fn (CategoryBudget $budget) => (float) ($budget->final_amount ?? 0));

        return [
            'categories_count' => $budgets->count(),
            'confirmed_count' => $confirmed->count(),
            'in_evaluation_count' => $inEvaluation->count(),
            'couple_budget' => $project->budget_amount !== null ? (float) $project->budget_amount : null,
            'estimated_total' => $estimatedTotal,
            'comparison_total' => $comparisonTotal,
            'final_total' => $finalTotal,
            'difference_total' => $comparisonTotal - $estimatedTotal,
            'completion' => $budgets->count() > 0 ? (int) round(($confirmed->count() / $budgets->count()) * 100) : 0,
        ];
    }

    public function getBudgetRows(): Collection
    {
        return $this->getRecord()
            ->loadMissing([
                'categoryBudgets.category',
                'categoryBudgets.supplierProposals.supplier',
            ])
            ->categoryBudgets
            ->sortBy(fn (CategoryBudget $budget): string => sprintf(
                '%05d-%s',
                (int) ($budget->category?->order ?? 99999),
                mb_strtolower((string) ($budget->category?->label ?? 'zzz'))
            ))
            ->values();
    }
}
