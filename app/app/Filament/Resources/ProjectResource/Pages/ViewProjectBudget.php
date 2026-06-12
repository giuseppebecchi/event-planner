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

    public function getBudgetSummary(): array
    {
        $project = $this->getRecord()->loadMissing('categoryBudgets');
        $budgets = auth()->user()?->isCustomer()
            ? $this->getBudgetRows()
            : $project->categoryBudgets;
        $confirmed = $budgets->where('budget_status', CategoryBudget::STATUS_CONFIRMED);
        $inEvaluation = $budgets->where('budget_status', CategoryBudget::STATUS_IN_EVALUATION);

        $estimatedTotal = (float) $budgets->sum(fn (CategoryBudget $budget) => (float) ($budget->initial_estimated_amount ?? 0));
        $comparisonTotal = (float) $budgets->sum(fn (CategoryBudget $budget) => (float) ($budget->comparison_amount ?? $budget->initial_estimated_amount ?? 0));
        $finalTotal = (float) $confirmed->sum(fn (CategoryBudget $budget) => (float) ($budget->final_amount ?? 0));
        $confirmedHypotheticalTotal = (float) $confirmed->sum(fn (CategoryBudget $budget) => (float) ($budget->initial_estimated_amount ?? 0));

        return [
            'categories_count' => $budgets->count(),
            'confirmed_count' => $confirmed->count(),
            'in_evaluation_count' => $inEvaluation->count(),
            'couple_budget' => $project->budget_amount !== null ? (float) $project->budget_amount : null,
            'estimated_total' => $estimatedTotal,
            'comparison_total' => $comparisonTotal,
            'final_total' => $finalTotal,
            'confirmed_hypothetical_total' => $confirmedHypotheticalTotal,
            'difference_total' => $comparisonTotal - $estimatedTotal,
            'completion' => $budgets->count() > 0 ? (int) round(($confirmed->count() / $budgets->count()) * 100) : 0,
        ];
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
