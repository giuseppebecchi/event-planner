<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\ProjectSeatingPlan;
use App\Support\SeatingPlanMapRenderer;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ViewProjectLayouts extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-layouts';

    protected static ?string $breadcrumb = 'Layouts';

    protected Width|string|null $maxContentWidth = Width::Full;

    public bool $showSeatingPlanEditor = false;

    public ?int $editingSeatingPlanId = null;

    public ?int $confirmDeleteSeatingPlanId = null;

    public array $seatingPlanForm = [
        'name' => '',
        'plan_type' => 'dinner',
        'initial_table_type' => 'round',
        'initial_table_count' => 12,
        'notes' => '',
    ];

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

    public function getSeatingPlans(): Collection
    {
        return $this->getRecord()
            ->loadMissing('seatingPlans.tables')
            ->seatingPlans
            ->sortBy([['sort_order', 'asc'], ['name', 'asc']])
            ->values();
    }

    public function getLayoutSummary(): array
    {
        $plans = $this->getSeatingPlans();

        return [
            'plans' => $plans->count(),
            'tables' => $plans->sum(fn (ProjectSeatingPlan $plan): int => $plan->tables->count()),
            'assigned' => $plans->sum(function (ProjectSeatingPlan $plan): int {
                return $plan->tables->sum(fn ($table): int => collect($table->guest_assignments_json ?? [])->filter()->count());
            }),
        ];
    }

    public function getPlanStats(ProjectSeatingPlan $plan): array
    {
        $tables = $plan->tables;
        $seats = $tables->sum(fn ($table): int => $table->seatCount());
        $assigned = $tables->sum(fn ($table): int => $table->assignedCount());

        return [
            'tables' => $tables->count(),
            'seats' => $seats,
            'assigned' => $assigned,
            'empty' => max(0, $seats - $assigned),
        ];
    }

    public function getPlanPreviewUrl(ProjectSeatingPlan $plan): ?string
    {
        if (! $plan->preview_image_path) {
            app(SeatingPlanMapRenderer::class)->storePreview($plan->loadMissing('tables'));
            $plan->refresh();
        }

        return Storage::disk('public')->url($plan->preview_image_path);
    }

    public function startCreateSeatingPlan(): void
    {
        $this->editingSeatingPlanId = null;
        $this->seatingPlanForm = [
            'name' => '',
            'plan_type' => 'dinner',
            'initial_table_type' => 'round',
            'initial_table_count' => 12,
            'notes' => '',
        ];
        $this->showSeatingPlanEditor = true;
    }

    public function editSeatingPlan(int $seatingPlanId): void
    {
        $plan = $this->findSeatingPlan($seatingPlanId);

        $this->editingSeatingPlanId = $plan->id;
        $this->seatingPlanForm = [
            'name' => $plan->name,
            'plan_type' => $plan->plan_type ?? '',
            'initial_table_type' => 'round',
            'initial_table_count' => $plan->tables->count(),
            'notes' => $plan->notes ?? '',
        ];
        $this->showSeatingPlanEditor = true;
    }

    public function closeSeatingPlanEditor(): void
    {
        $this->showSeatingPlanEditor = false;
        $this->editingSeatingPlanId = null;
        $this->seatingPlanForm = [
            'name' => '',
            'plan_type' => 'dinner',
            'initial_table_type' => 'round',
            'initial_table_count' => 12,
            'notes' => '',
        ];
    }

    public function saveSeatingPlan(): void
    {
        $data = validator($this->seatingPlanForm, [
            'name' => ['required', 'string', 'max:255'],
            'plan_type' => ['nullable', 'string', Rule::in(array_keys(ProjectSeatingPlan::PLAN_TYPE_OPTIONS))],
            'initial_table_type' => ['required_without:editingSeatingPlanId', 'string', Rule::in(['round', 'square'])],
            'initial_table_count' => ['required_without:editingSeatingPlanId', 'integer', 'min:0', 'max:200'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $payload = [
            'name' => trim((string) $data['name']),
            'plan_type' => $data['plan_type'] ?: null,
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ];

        $wasEditing = (bool) $this->editingSeatingPlanId;

        if ($wasEditing) {
            $this->findSeatingPlan($this->editingSeatingPlanId)->update($payload);
        } else {
            $plan = $this->getRecord()->seatingPlans()->create([
                ...$payload,
                'sort_order' => ((int) $this->getRecord()->seatingPlans()->max('sort_order')) + 1,
            ]);
            $this->createInitialTables(
                $plan,
                (int) ($data['initial_table_count'] ?? 0),
                (string) ($data['initial_table_type'] ?? 'round'),
            );
        }

        $this->getRecord()->unsetRelation('seatingPlans');
        $this->closeSeatingPlanEditor();

        Notification::make()
            ->title($wasEditing ? 'Layout updated' : 'Layout created')
            ->success()
            ->send();
    }

    public function promptDeleteSeatingPlan(int $seatingPlanId): void
    {
        $this->confirmDeleteSeatingPlanId = $this->findSeatingPlan($seatingPlanId)->id;
    }

    public function cancelDeleteSeatingPlan(): void
    {
        $this->confirmDeleteSeatingPlanId = null;
    }

    public function confirmDeleteSeatingPlan(): void
    {
        if (! $this->confirmDeleteSeatingPlanId) {
            return;
        }

        $this->findSeatingPlan($this->confirmDeleteSeatingPlanId)->delete();
        $this->confirmDeleteSeatingPlanId = null;
        $this->getRecord()->unsetRelation('seatingPlans');

        Notification::make()
            ->title('Layout deleted')
            ->success()
            ->send();
    }

    public function getPlanTypeOptions(): array
    {
        return ProjectSeatingPlan::PLAN_TYPE_OPTIONS;
    }

    public function getInitialTableTypeOptions(): array
    {
        return [
            'round' => 'Round tables',
            'square' => 'Square tables',
        ];
    }

    protected function findSeatingPlan(int $seatingPlanId): ProjectSeatingPlan
    {
        /** @var ProjectSeatingPlan $plan */
        $plan = $this->getRecord()
            ->seatingPlans()
            ->with('tables')
            ->findOrFail($seatingPlanId);

        return $plan;
    }

    protected function createInitialTables(ProjectSeatingPlan $plan, int $count, string $tableType): void
    {
        if ($count < 1) {
            return;
        }

        $columns = (int) ceil(sqrt($count));
        $gapX = 150;
        $gapY = 130;
        $startX = 180;
        $startY = 160;

        for ($index = 0; $index < $count; $index++) {
            $row = intdiv($index, $columns);
            $column = $index % $columns;
            $isSquare = $tableType === 'square';

            $plan->tables()->create([
                'name' => 'Table ' . ($index + 1),
                'center_x' => $startX + ($column * $gapX),
                'center_y' => $startY + ($row * $gapY),
                'rotation' => 0,
                'table_type' => $isSquare ? 'square' : 'round',
                'primary_dimension' => $isSquare ? 96 : 92,
                'secondary_dimension' => $isSquare ? 96 : 92,
                'seats_total' => $isSquare ? null : 8,
                'seats_by_side_json' => $isSquare ? ['top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2] : null,
                'guest_assignments_json' => [],
                'sort_order' => $index + 1,
            ]);
        }
    }
}
