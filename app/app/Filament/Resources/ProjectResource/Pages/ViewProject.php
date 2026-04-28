<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ViewProject extends ViewRecord
{
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project';

    protected static ?string $breadcrumb = 'Overview';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string | Htmlable
    {
        return (string) $this->getRecordTitle();
    }

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    public function getSubheading(): string | Htmlable | null
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
        $confirmed = $budgets->where('budget_status', 'confirmed');
        $inEvaluation = $budgets->where('budget_status', 'in_evaluation');

        $estimatedTotal = (float) $budgets->sum(fn (CategoryBudget $budget) => (float) ($budget->initial_estimated_amount ?? 0));
        $comparisonTotal = (float) $budgets->sum(fn (CategoryBudget $budget) => (float) ($budget->comparison_amount ?? $budget->initial_estimated_amount ?? 0));
        $finalTotal = (float) $confirmed->sum(fn (CategoryBudget $budget) => (float) ($budget->final_amount ?? 0));

        return [
            'categories_count' => $budgets->count(),
            'confirmed_count' => $confirmed->count(),
            'in_evaluation_count' => $inEvaluation->count(),
            'estimated_total' => $estimatedTotal,
            'comparison_total' => $comparisonTotal,
            'final_total' => $finalTotal,
            'completion' => $budgets->count() > 0 ? (int) round(($confirmed->count() / $budgets->count()) * 100) : 0,
        ];
    }

    public function getSupplierSummary(): array
    {
        $project = $this->getRecord()->loadMissing('categoryBudgetSuppliers.supplier');
        $proposals = $project->categoryBudgetSuppliers;

        return [
            'total' => $proposals->count(),
            'awaiting' => $proposals->where('availability_status', 'pending')->count(),
            'received' => $proposals->where('proposal_status', 'received')->count(),
            'shortlist' => $proposals->where('scouting_status', 'shortlist')->count(),
            'finalists' => $proposals->where('scouting_status', 'finalist')->count(),
            'confirmed' => $proposals->where('proposal_status', 'confirmed')->count(),
            'completion' => $proposals->count() > 0 ? (int) round(($proposals->where('proposal_status', 'confirmed')->count() / $proposals->count()) * 100) : 0,
        ];
    }

    public function getPreparationItems(): array
    {
        $project = $this->getRecord()->loadMissing('categoryBudgets', 'categoryBudgetSuppliers');
        $budgetSummary = $this->getBudgetSummary();
        $supplierSummary = $this->getSupplierSummary();

        return [
            ['label' => 'Event profile completed', 'done' => filled($project->event_start_date) && filled($project->locality) && filled($project->partner_one_name)],
            ['label' => 'Service budgets prepared', 'done' => $budgetSummary['categories_count'] > 0],
            ['label' => 'Supplier scouting started', 'done' => $supplierSummary['total'] > 0],
            ['label' => 'Confirmed suppliers selected', 'done' => $supplierSummary['confirmed'] > 0],
        ];
    }

    public function getTodoItems(): array
    {
        $project = $this->getRecord();
        $budgetSummary = $this->getBudgetSummary();
        $supplierSummary = $this->getSupplierSummary();

        $items = collect([
            blank($project->event_start_date) ? 'Set the event date to unlock countdown and planning milestones.' : null,
            $budgetSummary['categories_count'] === 0 ? 'Create the first category budgets for venue, flowers, catering and key services.' : null,
            $supplierSummary['awaiting'] > 0 ? sprintf('Review %d supplier proposals still awaiting a reply.', $supplierSummary['awaiting']) : null,
            $budgetSummary['in_evaluation_count'] > 0 ? sprintf('Compare and confirm %d budget categories still under evaluation.', $budgetSummary['in_evaluation_count']) : null,
            blank($project->final_guest_count) ? 'Confirm the working guest count to sharpen supplier comparisons.' : null,
        ])->filter()->values();

        return $items->isNotEmpty()
            ? $items->take(4)->all()
            : ['No urgent to-dos right now. The event dashboard is aligned with the current data.'];
    }

    public function getImportantItems(): array
    {
        $project = $this->getRecord();
        $budgetSummary = $this->getBudgetSummary();
        $supplierSummary = $this->getSupplierSummary();

        $items = collect([
            blank($project->reference_email) ? 'Reference email is still missing.' : null,
            blank($project->locality) && blank($project->region) ? 'Event area has not been defined yet.' : null,
            $budgetSummary['categories_count'] > 0 && $budgetSummary['confirmed_count'] === 0 ? 'No category budget is confirmed yet.' : null,
            $supplierSummary['total'] > 0 && $supplierSummary['confirmed'] === 0 ? 'No supplier proposal has been confirmed yet.' : null,
        ])->filter()->values();

        return $items->isNotEmpty()
            ? $items->take(4)->all()
            : ['No blocking items detected in the current event setup.'];
    }

    public function getInspirationTiles(): array
    {
        return [
            ['title' => 'Ceremony vision', 'caption' => 'Keep the ceremony concept visible while scouting the venue and floral setup.', 'tone' => 'sunrise'],
            ['title' => 'Reception atmosphere', 'caption' => 'Use this area for styling references, table mood and lighting direction.', 'tone' => 'olive'],
            ['title' => 'Guest experience', 'caption' => 'Track travel, hospitality and comfort details that influence supplier choices.', 'tone' => 'sky'],
            ['title' => 'Signature details', 'caption' => 'Collect inspiration for stationery, favors and small memorable touches.', 'tone' => 'sand'],
        ];
    }

    public function getDaysToGo(): ?int
    {
        $startDate = $this->getRecord()->event_start_date;

        if (! $startDate) {
            return null;
        }

        return now()->startOfDay()->diffInDays($startDate->startOfDay(), false);
    }

    public function getPlanningHighlights(): Collection
    {
        $project = $this->getRecord();
        $budgetSummary = $this->getBudgetSummary();
        $supplierSummary = $this->getSupplierSummary();

        return collect([
            [
                'label' => 'Budget categories',
                'value' => $budgetSummary['categories_count'],
                'caption' => $budgetSummary['confirmed_count'] . ' confirmed',
                'tone' => 'olive',
            ],
            [
                'label' => 'Supplier proposals',
                'value' => $supplierSummary['total'],
                'caption' => $supplierSummary['awaiting'] . ' awaiting',
                'tone' => 'blue',
            ],
            [
                'label' => 'Guest planning',
                'value' => $project->final_guest_count ?: $project->estimated_guest_count ?: 0,
                'caption' => $project->final_guest_count ? 'final headcount' : 'working estimate',
                'tone' => 'gold',
            ],
            [
                'label' => 'Days to go',
                'value' => $this->getDaysToGo() ?? '—',
                'caption' => $project->event_start_date?->format('d M Y') ?? 'date pending',
                'tone' => 'rose',
            ],
        ]);
    }
}
