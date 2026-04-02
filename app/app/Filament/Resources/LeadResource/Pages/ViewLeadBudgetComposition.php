<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class ViewLeadBudgetComposition extends Page
{
    use InteractsWithRecord;

    protected static string $resource = LeadResource::class;

    protected string $view = 'filament.resources.lead-resource.pages.view-lead-budget-composition';

    protected static ?string $breadcrumb = 'Budget composition';

    protected Width|string|null $maxContentWidth = Width::FiveExtraLarge;

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->data = $this->getInitialData();
    }

    public function getTitle(): string
    {
        return sprintf('%s Budget composition', $this->getRecordTitle());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('populateDefaults')
                ->label('Populate defaults')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->data = $this->getDefaultData();

                    Notification::make()
                        ->title('Budget defaults loaded')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->data;

        $this->getRecord()->forceFill([
            'budget_vendors' => $this->normalizeRows($state['budget_vendors'] ?? []),
            'budget_wedding_planner' => $this->normalizeRows($state['budget_wedding_planner'] ?? []),
            'budget_wedding_planner_extra_services' => $this->normalizeRows($state['budget_wedding_planner_extra_services'] ?? []),
            'budget_wedding_planner_special_packages' => $this->normalizeRows($state['budget_wedding_planner_special_packages'] ?? []),
        ])->save();

        Notification::make()
            ->title('Budget saved')
            ->success()
            ->send();
    }

    public function addSpecialPackage(): void
    {
        $this->data['budget_wedding_planner_special_packages'][] = [
            'label' => null,
            'notes' => null,
            'amount' => 0,
        ];
    }

    public function removeSpecialPackage(int $index): void
    {
        unset($this->data['budget_wedding_planner_special_packages'][$index]);
        $this->data['budget_wedding_planner_special_packages'] = array_values($this->data['budget_wedding_planner_special_packages']);
    }

    protected function getInitialData(): array
    {
        $record = $this->getRecord();

        return [
            'budget_vendors' => filled($record->budget_vendors) ? $this->hydrateVendorRows($record->budget_vendors) : $this->defaultVendorRows()->all(),
            'budget_wedding_planner' => filled($record->budget_wedding_planner)
                ? $this->collapseNotesIntoLabel($record->budget_wedding_planner)
                : $this->defaultWeddingPlannerRows(),
            'budget_wedding_planner_extra_services' => filled($record->budget_wedding_planner_extra_services)
                ? $this->collapseNotesIntoLabel($record->budget_wedding_planner_extra_services)
                : $this->defaultWeddingPlannerExtraServices(),
            'budget_wedding_planner_special_packages' => filled($record->budget_wedding_planner_special_packages)
                ? $this->collapseNotesIntoLabel($record->budget_wedding_planner_special_packages)
                : [],
        ];
    }

    protected function getDefaultData(): array
    {
        return [
            'budget_vendors' => $this->defaultVendorRows()->all(),
            'budget_wedding_planner' => $this->defaultWeddingPlannerRows(),
            'budget_wedding_planner_extra_services' => $this->defaultWeddingPlannerExtraServices(),
            'budget_wedding_planner_special_packages' => [],
        ];
    }

    protected function defaultVendorRows(): Collection
    {
        return Category::query()
            ->get()
            ->map(fn (Category $category): array => [
                'category_id' => $category->id,
                'label' => $category->label,
                'notes' => null,
                'amount' => 0,
            ]);
    }

    protected function hydrateVendorRows(array $rows): array
    {
        $categoryMap = Category::query()->get()->keyBy('id');

        return collect($rows)
            ->map(function (array $row) use ($categoryMap): array {
                $categoryId = $row['category_id'] ?? null;
                $category = $categoryId ? $categoryMap->get($categoryId) : null;

                return [
                    'category_id' => $categoryId,
                    'label' => $row['label'] ?? $category?->label,
                    'notes' => $row['notes'] ?? null,
                    'amount' => blank($row['amount'] ?? null) ? 0 : $row['amount'],
                ];
            })
            ->all();
    }

    protected function defaultWeddingPlannerRows(): array
    {
        return [[
            'label' => "Planning and\ncoordination fee for\nwedding day welcome\ndinner\nfrom n to max n guests",
            'notes' => null,
            'amount' => 0,
        ]];
    }

    protected function defaultWeddingPlannerExtraServices(): array
    {
        return [
            ['label' => 'Management of guests accommodation out of the venue', 'notes' => null, 'amount' => 300],
            ['label' => "Extra guests on the wedding day\nextra every 10 guests", 'notes' => null, 'amount' => 100],
            ['label' => 'Help with guests transfers on the wedding day', 'notes' => null, 'amount' => 300],
            ['label' => "Extra coordinator needed if the venue is not walking distance from the church\nfrom EUR 300 each per day", 'notes' => null, 'amount' => 300],
            ['label' => 'Second venue research', 'notes' => null, 'amount' => 500],
            ['label' => "Extra pre and post wedding events (planning and coordination)\neach event", 'notes' => null, 'amount' => 800],
            ['label' => "Extra video calls\neach call", 'notes' => null, 'amount' => 50],
        ];
    }

    protected function collapseNotesIntoLabel(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $label = trim((string) ($row['label'] ?? ''));
                $notes = trim((string) ($row['notes'] ?? ''));

                if ($notes !== '') {
                    $label = trim($label . "\n" . $notes);
                }

                return [
                    'category_id' => $row['category_id'] ?? null,
                    'label' => $label,
                    'notes' => null,
                    'amount' => blank($row['amount'] ?? null) ? 0 : $row['amount'],
                ];
            })
            ->values()
            ->all();
    }

    protected function normalizeRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                return [
                    'category_id' => $row['category_id'] ?? null,
                    'label' => $row['label'] ?? null,
                    'notes' => $row['notes'] ?? null,
                    'amount' => blank($row['amount'] ?? null) ? 0 : (float) str_replace(',', '.', (string) $row['amount']),
                ];
            })
            ->values()
            ->all();
    }
}
