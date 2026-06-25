<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Models\ProjectChecklistOption;
use App\Models\ProjectSeatingPlan;
use App\Support\SeatingPlanMapRenderer;
use App\Models\CategoryBudgetSupplier;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ViewProjectRecap extends ViewProjectTimeline
{
    protected string $view = 'filament.resources.project-resource.pages.view-project-recap';

    protected static ?string $breadcrumb = 'Recap';

    protected Width|string|null $maxContentWidth = Width::Full;

    public $recapRailImageUpload = null;

    public function getRecapChecklistItems(): Collection
    {
        return $this->getRecord()
            ->projectChecklistOptions()
            ->with(['supplier', 'checklist.category'])
            ->where('enabled', true)
            ->where('insert_into_recap', true)
            ->orderBy('due_date')
            ->orderBy('order')
            ->get()
            ->filter(fn (ProjectChecklistOption $item): bool => filled($item->response) || filled($item->details) || filled($item->title))
            ->values();
    }

    public function saveRecapRailImage(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $data = validator(
            ['image' => $this->recapRailImageUpload],
            ['image' => ['required', 'image', 'max:20480']]
        )->validate();

        $project = $this->getRecord();

        if ($project->recap_left_rail_image_path) {
            Storage::disk('public')->delete($project->recap_left_rail_image_path);
        }

        $project->forceFill([
            'recap_left_rail_image_path' => $data['image']->store('projects/recap', 'public'),
        ])->save();

        $this->recapRailImageUpload = null;
        $this->record = $project->fresh();

        Notification::make()
            ->title('Recap image updated')
            ->success()
            ->send();
    }

    public function resetRecapRailImage(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $project = $this->getRecord();

        if ($project->recap_left_rail_image_path) {
            Storage::disk('public')->delete($project->recap_left_rail_image_path);
        }

        $project->forceFill(['recap_left_rail_image_path' => null])->save();
        $this->record = $project->fresh();

        Notification::make()
            ->title('Default recap image restored')
            ->success()
            ->send();
    }

    public function getRecapRailImageUrl(): string
    {
        $project = $this->getRecord();

        if ($project->recap_left_rail_image_path) {
            return Storage::disk('public')->url($project->recap_left_rail_image_path);
        }

        return asset('images/pdf/timeline-left-rail.png');
    }

    public function getRecapSeatingPlans(): Collection
    {
        return $this->getRecord()
            ->seatingPlans()
            ->with('tables')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getRecapConfirmedSuppliers(): Collection
    {
        return $this->getRecord()
            ->loadMissing('categoryBudgetSuppliers.supplier.category', 'categoryBudgetSuppliers.category')
            ->categoryBudgetSuppliers
            ->filter(fn (CategoryBudgetSupplier $proposal): bool => $proposal->proposal_status === CategoryBudgetSupplier::STATUS_CONFIRMED && $proposal->supplier)
            ->sortBy(fn (CategoryBudgetSupplier $proposal): string => sprintf(
                '%s-%s',
                $proposal->category?->label ?? $proposal->supplier?->category?->label ?? '',
                $proposal->supplier?->name ?? ''
            ))
            ->map(fn (CategoryBudgetSupplier $proposal): array => $this->confirmedSupplierPdfPayload($proposal))
            ->values();
    }

    protected function recapSeatingPlanPdfItems(): Collection
    {
        return $this->getRecapSeatingPlans()
            ->map(function (ProjectSeatingPlan $plan): array {
                $preview = $plan->preview_image_path
                    ? $this->imagePathToDataUri($plan->preview_image_path)
                    : null;

                $mapSvg = $preview ? null : app(SeatingPlanMapRenderer::class)->render($plan->loadMissing(['tables', 'layoutElements']), [], true);

                return [
                    'name' => $plan->name,
                    'type' => ProjectSeatingPlan::PLAN_TYPE_OPTIONS[$plan->plan_type] ?? ($plan->plan_type ?: 'Layout'),
                    'notes' => $plan->notes,
                    'preview' => $preview,
                    'map_svg' => $mapSvg,
                    'tables' => $plan->tables
                        ->map(fn ($table): array => [
                            'name' => $table->name,
                            'type' => $table->table_type ? ($table::TABLE_TYPE_OPTIONS[$table->table_type] ?? $table->table_type) : 'Seating',
                            'seats' => $table->seatCount(),
                        ])
                        ->values(),
                ];
            });
    }
}
