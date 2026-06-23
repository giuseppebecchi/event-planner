<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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

    public function editBudgetCategoryAction(): Action
    {
        return Action::make('editBudgetCategory')
            ->label('Edit budget')
            ->modalHeading('Edit category budget')
            ->modalSubmitActionLabel('Save budget')
            ->visible(fn (): bool => ! auth()->user()?->isCustomer())
            ->fillForm(function (array $arguments): array {
                $budget = $this->findBudget((int) ($arguments['budget'] ?? 0));

                return [
                    'initial_estimated_amount' => $budget->initial_estimated_amount,
                    'comparison_amount' => $budget->comparison_amount,
                    'final_amount' => $budget->final_amount,
                    'budget_status' => $budget->budget_status,
                    'notes' => $budget->notes,
                ];
            })
            ->form([
                TextInput::make('initial_estimated_amount')
                    ->label('Estimated budget')
                    ->prefix('EUR')
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01'),
                TextInput::make('comparison_amount')
                    ->label('Working budget')
                    ->prefix('EUR')
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01'),
                TextInput::make('final_amount')
                    ->label('Final budget')
                    ->prefix('EUR')
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01'),
                Select::make('budget_status')
                    ->label('Status')
                    ->options(CategoryBudget::STATUS_OPTIONS)
                    ->required(),
                Textarea::make('notes')
                    ->rows(3),
            ])
            ->action(function (array $data, array $arguments): void {
                if (auth()->user()?->isCustomer()) {
                    return;
                }

                $budget = $this->findBudget((int) ($arguments['budget'] ?? 0));
                $budget->update([
                    'initial_estimated_amount' => filled($data['initial_estimated_amount'] ?? null) ? (float) $data['initial_estimated_amount'] : null,
                    'comparison_amount' => filled($data['comparison_amount'] ?? null) ? (float) $data['comparison_amount'] : null,
                    'final_amount' => filled($data['final_amount'] ?? null) ? (float) $data['final_amount'] : null,
                    'budget_status' => $data['budget_status'] ?? CategoryBudget::STATUS_HYPOTHETICAL,
                    'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                ]);

                $this->record = $this->getRecord()->fresh();

                Notification::make()
                    ->title('Budget updated')
                    ->success()
                    ->send();
            });
    }

    public function addServiceCategoryAction(): Action
    {
        return Action::make('addServiceCategory')
            ->label('Add Service Category')
            ->modalHeading('Add Service Category')
            ->modalSubmitActionLabel('Add category')
            ->visible(fn (): bool => ! auth()->user()?->isCustomer())
            ->disabled(fn (): bool => ! $this->hasAvailableServiceCategories())
            ->form([
                Select::make('category_id')
                    ->label('Service Category')
                    ->options(fn (): array => $this->availableServiceCategoryOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('initial_estimated_amount')
                    ->label('Estimated budget')
                    ->prefix('EUR')
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01'),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                if (auth()->user()?->isCustomer()) {
                    return;
                }

                $project = $this->getRecord();
                $categoryId = (int) $data['category_id'];
                $alreadyExists = CategoryBudget::query()
                    ->where('project_id', $project->getKey())
                    ->where('category_id', $categoryId)
                    ->exists();

                if ($alreadyExists) {
                    Notification::make()
                        ->title('Service Category already exists in this budget')
                        ->warning()
                        ->send();

                    return;
                }

                CategoryBudget::query()->create([
                    'project_id' => $project->getKey(),
                    'category_id' => $categoryId,
                    'initial_estimated_amount' => filled($data['initial_estimated_amount'] ?? null)
                        ? (float) $data['initial_estimated_amount']
                        : null,
                    'budget_status' => CategoryBudget::STATUS_HYPOTHETICAL,
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->record = $project->fresh();

                Notification::make()
                    ->title('Service Category added')
                    ->success()
                    ->send();
            });
    }

    public function hasAvailableServiceCategories(): bool
    {
        return $this->availableServiceCategoryQuery()->exists();
    }

    protected function availableServiceCategoryOptions(): array
    {
        return $this->availableServiceCategoryQuery()
            ->pluck('label', 'id')
            ->all();
    }

    protected function availableServiceCategoryQuery()
    {
        $usedCategoryIds = $this->getRecord()
            ->categoryBudgets()
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->all();

        return Category::query()
            ->when($usedCategoryIds !== [], fn ($query) => $query->whereNotIn('id', $usedCategoryIds));
    }

    protected function findBudget(int $budgetId): CategoryBudget
    {
        return $this->getRecord()
            ->categoryBudgets()
            ->whereKey($budgetId)
            ->firstOrFail();
    }

    public function getBudgetSummary(): array
    {
        $project = $this->getRecord()->loadMissing('categoryBudgets.category');
        $allBudgets = auth()->user()?->isCustomer()
            ? $this->getBudgetRows()
            : $project->categoryBudgets;
        $venueBudgets = $allBudgets->filter(fn (CategoryBudget $budget): bool => $this->isVenueBudget($budget));
        $venueExcluded = ! (bool) $project->venue_included_in_budget;
        $budgets = $venueExcluded
            ? $allBudgets->reject(fn (CategoryBudget $budget): bool => $this->isVenueBudget($budget))->values()
            : $allBudgets;
        $confirmed = $budgets->where('budget_status', CategoryBudget::STATUS_CONFIRMED);
        $inEvaluation = $budgets->where('budget_status', CategoryBudget::STATUS_IN_EVALUATION);

        $estimatedTotal = (float) $budgets->sum(fn (CategoryBudget $budget) => (float) ($budget->initial_estimated_amount ?? 0));
        $comparisonTotal = (float) $budgets->sum(fn (CategoryBudget $budget) => (float) ($budget->comparison_amount ?? $budget->initial_estimated_amount ?? 0));
        $finalTotal = (float) $confirmed->sum(fn (CategoryBudget $budget) => (float) ($budget->final_amount ?? 0));
        $confirmedHypotheticalTotal = (float) $confirmed->sum(fn (CategoryBudget $budget) => (float) ($budget->initial_estimated_amount ?? 0));
        $venueEstimatedTotal = (float) $venueBudgets->sum(fn (CategoryBudget $budget) => (float) ($budget->initial_estimated_amount ?? 0));
        $venueComparisonTotal = (float) $venueBudgets->sum(fn (CategoryBudget $budget) => (float) ($budget->comparison_amount ?? $budget->initial_estimated_amount ?? 0));
        $venueFinalTotal = (float) $venueBudgets
            ->where('budget_status', CategoryBudget::STATUS_CONFIRMED)
            ->sum(fn (CategoryBudget $budget) => (float) ($budget->final_amount ?? 0));
        $venueSeparatedAmount = $venueFinalTotal ?: $venueComparisonTotal ?: $venueEstimatedTotal;
        $rawCoupleBudget = $project->budget_amount !== null ? (float) $project->budget_amount : null;

        return [
            'categories_count' => $budgets->count(),
            'all_categories_count' => $allBudgets->count(),
            'confirmed_count' => $confirmed->count(),
            'in_evaluation_count' => $inEvaluation->count(),
            'couple_budget' => $rawCoupleBudget,
            'raw_couple_budget' => $rawCoupleBudget,
            'estimated_total' => $estimatedTotal,
            'comparison_total' => $comparisonTotal,
            'final_total' => $finalTotal,
            'confirmed_hypothetical_total' => $confirmedHypotheticalTotal,
            'difference_total' => $comparisonTotal - $estimatedTotal,
            'completion' => $budgets->count() > 0 ? (int) round(($confirmed->count() / $budgets->count()) * 100) : 0,
            'venue_excluded' => $venueExcluded,
            'venue_budget_count' => $venueBudgets->count(),
            'venue_estimated_total' => $venueEstimatedTotal,
            'venue_comparison_total' => $venueComparisonTotal,
            'venue_final_total' => $venueFinalTotal,
            'venue_separated_amount' => $venueSeparatedAmount,
        ];
    }

    protected function isVenueBudget(CategoryBudget $budget): bool
    {
        $category = $budget->category;

        return strcasecmp((string) ($category?->label ?? ''), 'Venue') === 0
            || strcasecmp((string) ($category?->label_it ?? ''), 'Location') === 0;
    }

    public function getBudgetRows(): Collection
    {
        $rows = $this->getRecord()
            ->loadMissing([
                'categoryBudgets.category',
                'categoryBudgets.supplierProposals.supplier',
            ])
            ->categoryBudgets
            ->when(auth()->user()?->isCustomer(), function (Collection $budgets): Collection {
                return $budgets
                    ->map(function (CategoryBudget $budget): CategoryBudget {
                        $budget->setRelation(
                            'supplierProposals',
                            $budget->supplierProposals
                                ->filter(fn (CategoryBudgetSupplier $proposal): bool => $proposal->scouting_status === 'shortlist')
                                ->values()
                        );

                        return $budget;
                    })
                    ->filter(fn (CategoryBudget $budget): bool => $budget->supplierProposals->isNotEmpty());
            })
            ->sortBy(fn (CategoryBudget $budget): string => sprintf(
                '%05d-%s',
                (int) ($budget->category?->order ?? 99999),
                mb_strtolower((string) ($budget->category?->label ?? 'zzz'))
            ))
            ->values();

        return $rows;
    }
}
