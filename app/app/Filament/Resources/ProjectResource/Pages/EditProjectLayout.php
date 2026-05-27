<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\ProjectSeatingPlan;
use App\Models\ProjectTable;
use App\Support\SeatingPlanMapRenderer;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Renderless;
use Livewire\WithFileUploads;

class EditProjectLayout extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.edit-project-layout';

    protected static ?string $breadcrumb = 'Layout editor';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ProjectSeatingPlan $currentSeatingPlan;

    public $backgroundUpload = null;

    public function mount(int|string $record, int|string $seatingPlan): void
    {
        $this->record = $this->resolveRecord($record);

        abort_if(auth()->user()?->isCustomer(), 403);

        $this->currentSeatingPlan = $this->getRecord()
            ->seatingPlans()
            ->with('tables')
            ->findOrFail($seatingPlan);
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

    public function getEditorTables(): array
    {
        return $this->currentSeatingPlan
            ->loadMissing('tables')
            ->tables
            ->map(fn (ProjectTable $table): array => [
                'id' => $table->id,
                'name' => $table->name ?: ('Table ' . $table->sort_order),
                'center_x' => (float) $table->center_x,
                'center_y' => (float) $table->center_y,
                'rotation' => (float) $table->rotation,
                'table_type' => $table->table_type,
                'primary_dimension' => (float) $table->primary_dimension,
                'secondary_dimension' => $table->secondary_dimension !== null ? (float) $table->secondary_dimension : (float) $table->primary_dimension,
                'seats_total' => $table->seats_total,
                'seats_by_side_json' => $table->seats_by_side_json ?? ['top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2],
                'guest_assignments_json' => $table->guest_assignments_json ?? [],
                'sort_order' => $table->sort_order,
            ])
            ->values()
            ->all();
    }

    public function getBackgroundImageUrl(): ?string
    {
        if (! $this->currentSeatingPlan->background_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->currentSeatingPlan->background_image_path);
    }

    public function getPreviewImageUrl(): ?string
    {
        if (! $this->currentSeatingPlan->preview_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->currentSeatingPlan->preview_image_path);
    }

    public function getViewportState(): array
    {
        return [
            'zoom' => (float) ($this->currentSeatingPlan->viewport_zoom ?: 1),
            'x' => (int) ($this->currentSeatingPlan->viewport_x ?: 0),
            'y' => (int) ($this->currentSeatingPlan->viewport_y ?: 0),
        ];
    }

    public function saveBackgroundImage(): void
    {
        $data = validator(
            ['backgroundUpload' => $this->backgroundUpload],
            ['backgroundUpload' => ['required', 'image', 'max:20480']]
        )->validate();

        if ($this->currentSeatingPlan->background_image_path) {
            Storage::disk('public')->delete($this->currentSeatingPlan->background_image_path);
        }

        $this->currentSeatingPlan->forceFill([
            'background_image_path' => $data['backgroundUpload']->store('projects/seating-plans', 'public'),
        ])->save();

        $this->backgroundUpload = null;
        $this->currentSeatingPlan->refresh();
        $this->refreshPreviewImage();
        $this->dispatch('seating-background-updated', url: $this->getBackgroundImageUrl());

        Notification::make()->title('Background image updated')->success()->send();
    }

    public function updatedBackgroundUpload(): void
    {
        if (! $this->backgroundUpload) {
            return;
        }

        $this->saveBackgroundImage();
    }

    public function removeBackgroundImage(): void
    {
        if ($this->currentSeatingPlan->background_image_path) {
            Storage::disk('public')->delete($this->currentSeatingPlan->background_image_path);
        }

        $this->currentSeatingPlan->forceFill(['background_image_path' => null])->save();
        $this->currentSeatingPlan->refresh();
        $this->refreshPreviewImage();
        $this->dispatch('seating-background-updated', url: null);

        Notification::make()->title('Background image removed')->success()->send();
    }

    public function addTable(string $type): array
    {
        return $this->addTables($type, 1, 8)[0];
    }

    public function addTables(string $type, int $count = 1, int $seats = 8): array
    {
        if (! in_array($type, ['round', 'square', 'rectangular', 'chair_row'], true)) {
            $type = 'round';
        }

        $count = max(1, min(80, $count));
        $seats = max(0, min(80, $seats));
        $nextOrder = ((int) $this->currentSeatingPlan->tables()->max('sort_order')) + 1;
        $nextChairRowNumber = ((int) $this->currentSeatingPlan->tables()->where('table_type', 'chair_row')->count()) + 1;
        $isSquare = $type === 'square';
        $isRectangular = $type === 'rectangular';
        $isChairRow = $type === 'chair_row';
        $created = [];
        $width = $isChairRow ? ProjectTable::chairRowWidth($seats) : ($isRectangular ? 150 : ($isSquare ? 96 : 92));
        $height = $isChairRow ? ProjectTable::CHAIR_ROW_HEIGHT : ($isRectangular ? 82 : ($isSquare ? 96 : 92));
        $gap = $isChairRow ? 58 : 28;
        $totalWidth = ($count * $width) + (($count - 1) * $gap);
        $startX = $isChairRow ? 700 : max(120, 1320 - $totalWidth + ($width / 2));
        $startY = $isChairRow ? 120 : 800;

        for ($index = 0; $index < $count; $index++) {
            $order = $nextOrder + $index;
            $sideSeats = (int) floor($seats / 4);
            $remainingSeats = $seats % 4;

            $table = $this->currentSeatingPlan->tables()->create([
                'name' => $isChairRow ? ('Row ' . ($nextChairRowNumber + $index)) : ('Table ' . $order),
                'center_x' => $isChairRow ? $startX : $startX + ($index * ($width + $gap)),
                'center_y' => $isChairRow ? $startY + ($index * $gap) : $startY,
                'rotation' => 0,
                'table_type' => $isChairRow ? 'chair_row' : ($isRectangular ? 'rectangular' : ($isSquare ? 'square' : 'round')),
                'primary_dimension' => $width,
                'secondary_dimension' => $height,
                'seats_total' => ($isSquare || $isRectangular) ? null : $seats,
                'seats_by_side_json' => ($isSquare || $isRectangular) ? [
                    'top' => $sideSeats + ($remainingSeats > 0 ? 1 : 0),
                    'right' => $sideSeats + ($remainingSeats > 1 ? 1 : 0),
                    'bottom' => $sideSeats + ($remainingSeats > 2 ? 1 : 0),
                    'left' => $sideSeats,
                ] : null,
                'guest_assignments_json' => [],
                'sort_order' => $order,
            ]);

            $created[] = [
                'id' => $table->id,
                'name' => $table->name,
                'center_x' => (float) $table->center_x,
                'center_y' => (float) $table->center_y,
                'rotation' => (float) $table->rotation,
                'table_type' => $table->table_type,
                'primary_dimension' => (float) $table->primary_dimension,
                'secondary_dimension' => (float) $table->secondary_dimension,
                'seats_total' => $table->seats_total,
                'seats_by_side_json' => $table->seats_by_side_json ?? ['top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2],
                'guest_assignments_json' => [],
                'sort_order' => $table->sort_order,
            ];
        }

        $this->currentSeatingPlan->unsetRelation('tables');
        $this->refreshPreviewImage();

        return $created;
    }

    #[Renderless]
    public function saveTables(array $tables): void
    {
        $data = validator(
            ['tables' => $tables],
            [
                'tables' => ['array'],
                'tables.*.id' => ['required', 'integer', 'exists:project_tables,id'],
                'tables.*.name' => ['required', 'string', 'max:255'],
                'tables.*.center_x' => ['required', 'numeric'],
                'tables.*.center_y' => ['required', 'numeric'],
                'tables.*.rotation' => ['required', 'numeric'],
                'tables.*.table_type' => ['required', 'string', Rule::in(array_keys(ProjectTable::TABLE_TYPE_OPTIONS))],
                'tables.*.primary_dimension' => ['required', 'numeric', 'min:20', 'max:600'],
                'tables.*.secondary_dimension' => ['nullable', 'numeric', 'min:20', 'max:600'],
                'tables.*.seats_total' => ['nullable', 'integer', 'min:0', 'max:80'],
                'tables.*.seats_by_side_json' => ['nullable', 'array'],
                'tables.*.seats_by_side_json.top' => ['nullable', 'integer', 'min:0', 'max:40'],
                'tables.*.seats_by_side_json.right' => ['nullable', 'integer', 'min:0', 'max:40'],
                'tables.*.seats_by_side_json.bottom' => ['nullable', 'integer', 'min:0', 'max:40'],
                'tables.*.seats_by_side_json.left' => ['nullable', 'integer', 'min:0', 'max:40'],
            ]
        )->validate();

        $allowedIds = $this->currentSeatingPlan->tables()->pluck('id')->all();

        foreach ($data['tables'] as $tableData) {
            if (! in_array((int) $tableData['id'], $allowedIds, true)) {
                continue;
            }

            $isRound = in_array($tableData['table_type'], ['round', 'oval'], true);
            $isChairRow = $tableData['table_type'] === 'chair_row';
            $chairRowSeats = max(0, min(80, (int) ($tableData['seats_total'] ?? 0)));
            $primaryDimension = $isChairRow ? ProjectTable::chairRowWidth($chairRowSeats) : $tableData['primary_dimension'];
            $secondaryDimension = $isChairRow ? ProjectTable::CHAIR_ROW_HEIGHT : ($tableData['secondary_dimension'] ?? $tableData['primary_dimension']);

            ProjectTable::query()
                ->whereKey($tableData['id'])
                ->where('project_seating_plan_id', $this->currentSeatingPlan->id)
                ->update([
                    'name' => trim((string) $tableData['name']),
                    'center_x' => $tableData['center_x'],
                    'center_y' => $tableData['center_y'],
                    'rotation' => $tableData['rotation'],
                    'table_type' => $tableData['table_type'],
                    'primary_dimension' => $primaryDimension,
                    'secondary_dimension' => $secondaryDimension,
                    'seats_total' => ($isRound || $isChairRow) ? (int) ($tableData['seats_total'] ?? 0) : null,
                    'seats_by_side_json' => ($isRound || $isChairRow) ? null : ($tableData['seats_by_side_json'] ?? ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]),
                ]);
        }

        $this->currentSeatingPlan->unsetRelation('tables');
        $this->refreshPreviewImage();
    }

    #[Renderless]
    public function saveViewportState(array $viewport): void
    {
        $data = validator(
            ['viewport' => $viewport],
            [
                'viewport.zoom' => ['required', 'numeric', 'min:0.35', 'max:2.5'],
                'viewport.x' => ['required', 'integer', 'min:-4000', 'max:4000'],
                'viewport.y' => ['required', 'integer', 'min:-4000', 'max:4000'],
            ]
        )->validate();

        $this->currentSeatingPlan->forceFill([
            'viewport_zoom' => $data['viewport']['zoom'],
            'viewport_x' => $data['viewport']['x'],
            'viewport_y' => $data['viewport']['y'],
        ])->save();
    }

    #[Renderless]
    public function refreshPreviewImage(): void
    {
        app(SeatingPlanMapRenderer::class)->storePreview($this->currentSeatingPlan->refresh()->load('tables'));
    }

    public function deleteTable(int $tableId): void
    {
        $deleted = $this->currentSeatingPlan
            ->tables()
            ->whereKey($tableId)
            ->delete();

        if (! $deleted) {
            return;
        }

        $this->currentSeatingPlan->unsetRelation('tables');
        $this->refreshPreviewImage();

        Notification::make()->title('Table deleted')->success()->send();
    }

    public function duplicateTable(int $tableId): ?int
    {
        $source = $this->currentSeatingPlan
            ->tables()
            ->whereKey($tableId)
            ->first();

        if (! $source) {
            return null;
        }

        $nextOrder = ((int) $this->currentSeatingPlan->tables()->max('sort_order')) + 1;
        $nextChairRowNumber = ((int) $this->currentSeatingPlan->tables()->where('table_type', 'chair_row')->count()) + 1;
        $seatMargin = 72;
        $isChairRow = $source->table_type === 'chair_row';
        $nextCenterX = $isChairRow
            ? (float) $source->center_x
            : min(1350, (float) $source->center_x + (float) $source->primary_dimension + $seatMargin);
        $nextCenterY = $isChairRow
            ? (float) $source->center_y + 58
            : (float) $source->center_y;

        $table = $this->currentSeatingPlan->tables()->create([
            'name' => $isChairRow ? ('Row ' . $nextChairRowNumber) : ('Table ' . $nextOrder),
            'center_x' => $nextCenterX,
            'center_y' => $nextCenterY,
            'rotation' => $source->rotation,
            'table_type' => $source->table_type,
            'primary_dimension' => $source->primary_dimension,
            'secondary_dimension' => $source->secondary_dimension,
            'seats_total' => $source->seats_total,
            'seats_by_side_json' => $source->seats_by_side_json,
            'guest_assignments_json' => [],
            'sort_order' => $nextOrder,
        ]);

        $this->currentSeatingPlan->unsetRelation('tables');
        $this->refreshPreviewImage();

        Notification::make()->title('Table duplicated')->success()->send();

        return $table->id;
    }
}
