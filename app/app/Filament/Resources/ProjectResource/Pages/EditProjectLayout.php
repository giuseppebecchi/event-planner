<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\ProjectLayoutElement;
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
            ->with(['tables', 'layoutElements'])
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
                'curve_count' => (int) ($table->curve_count ?? 0),
                'curve_type' => $table->curve_type ?: ($table->table_type === 'chair_row' ? 'none' : 'medium'),
                'guest_assignments_json' => $table->guest_assignments_json ?? [],
                'sort_order' => $table->sort_order,
            ])
            ->values()
            ->all();
    }

    public function getLayoutElements(): array
    {
        return $this->currentSeatingPlan
            ->loadMissing('layoutElements')
            ->layoutElements
            ->map(fn (ProjectLayoutElement $element): array => [
                'id' => $element->id,
                'element_type' => $element->element_type,
                'shape' => $element->shape ?: 'rectangle',
                'label' => $element->label ?: '',
                'center_x' => (float) $element->center_x,
                'center_y' => (float) $element->center_y,
                'rotation' => (float) $element->rotation,
                'width' => (float) $element->width,
                'height' => (float) $element->height,
                'background_color' => $element->background_color ?: '#f3eadc',
                'sort_order' => $element->sort_order,
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
        if (! in_array($type, ['round', 'square', 'rectangular', 'long_table', 'chair_row'], true)) {
            $type = 'round';
        }

        $count = max(1, min(80, $count));
        $seats = max(0, min(80, $seats));
        $nextOrder = ((int) $this->currentSeatingPlan->tables()->max('sort_order')) + 1;
        $nextChairRowNumber = ((int) $this->currentSeatingPlan->tables()->where('table_type', 'chair_row')->count()) + 1;
        $isSquare = $type === 'square';
        $isRectangular = $type === 'rectangular';
        $isLongTable = $type === 'long_table';
        $isChairRow = $type === 'chair_row';
        $created = [];
        $width = $isChairRow ? ProjectTable::chairRowWidth($seats) : ($isLongTable ? ProjectTable::LONG_TABLE_DEFAULT_LENGTH : ($isRectangular ? 150 : ($isSquare ? 96 : 92)));
        $height = $isChairRow ? ProjectTable::CHAIR_ROW_HEIGHT : ($isLongTable ? ProjectTable::LONG_TABLE_WIDTH : ($isRectangular ? 82 : ($isSquare ? 96 : 92)));
        $gap = $isChairRow ? 58 : ($isLongTable ? 48 : 28);
        $totalWidth = ($count * $width) + (($count - 1) * $gap);
        $startX = $isChairRow ? 700 : ($isLongTable ? 700 : max(120, 1320 - $totalWidth + ($width / 2)));
        $startY = $isChairRow ? 120 : ($isLongTable ? 760 : 800);

        for ($index = 0; $index < $count; $index++) {
            $order = $nextOrder + $index;
            $sideSeats = (int) floor($seats / 4);
            $remainingSeats = $seats % 4;

            $table = $this->currentSeatingPlan->tables()->create([
                'name' => $isChairRow ? ('Row ' . ($nextChairRowNumber + $index)) : ('Table ' . $order),
                'center_x' => ($isChairRow || $isLongTable) ? $startX : $startX + ($index * ($width + $gap)),
                'center_y' => $isChairRow ? $startY + ($index * $gap) : ($isLongTable ? $startY - ($index * ($height + 70)) : $startY),
                'rotation' => 0,
                'table_type' => $isChairRow ? 'chair_row' : ($isLongTable ? 'long_table' : ($isRectangular ? 'rectangular' : ($isSquare ? 'square' : 'round'))),
                'primary_dimension' => $width,
                'secondary_dimension' => $height,
                'seats_total' => ($isSquare || $isRectangular || $isLongTable) ? null : $seats,
                'seats_by_side_json' => $isLongTable ? [
                    'top' => 8,
                    'right' => 1,
                    'bottom' => 8,
                    'left' => 1,
                ] : (($isSquare || $isRectangular) ? [
                    'top' => $sideSeats + ($remainingSeats > 0 ? 1 : 0),
                    'right' => $sideSeats + ($remainingSeats > 1 ? 1 : 0),
                    'bottom' => $sideSeats + ($remainingSeats > 2 ? 1 : 0),
                    'left' => $sideSeats,
                ] : null),
                'curve_count' => $isLongTable ? 0 : null,
                'curve_type' => $isLongTable ? 'medium' : ($isChairRow ? 'none' : null),
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
                'curve_count' => (int) ($table->curve_count ?? 0),
                'curve_type' => $table->curve_type ?: ($table->table_type === 'chair_row' ? 'none' : 'medium'),
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
                'tables.*.primary_dimension' => ['required', 'numeric', 'min:20', 'max:2000'],
                'tables.*.secondary_dimension' => ['nullable', 'numeric', 'min:20', 'max:2000'],
                'tables.*.seats_total' => ['nullable', 'integer', 'min:0', 'max:80'],
                'tables.*.seats_by_side_json' => ['nullable', 'array'],
                'tables.*.seats_by_side_json.top' => ['nullable', 'integer', 'min:0', 'max:40'],
                'tables.*.seats_by_side_json.right' => ['nullable', 'integer', 'min:0', 'max:40'],
                'tables.*.seats_by_side_json.bottom' => ['nullable', 'integer', 'min:0', 'max:40'],
                'tables.*.seats_by_side_json.left' => ['nullable', 'integer', 'min:0', 'max:40'],
                'tables.*.curve_count' => ['nullable', 'integer', 'min:0', 'max:4'],
                'tables.*.curve_type' => ['nullable', 'string', Rule::in(array_unique(array_merge(array_keys(ProjectTable::LONG_TABLE_CURVE_TYPES), array_keys(ProjectTable::CHAIR_ROW_CURVE_TYPES))))],
            ]
        )->validate();

        $allowedIds = $this->currentSeatingPlan->tables()->pluck('id')->all();

        foreach ($data['tables'] as $tableData) {
            if (! in_array((int) $tableData['id'], $allowedIds, true)) {
                continue;
            }

            $isRound = in_array($tableData['table_type'], ['round', 'oval'], true);
            $isChairRow = $tableData['table_type'] === 'chair_row';
            $isLongTable = $tableData['table_type'] === 'long_table';
            $chairRowSeats = max(0, min(80, (int) ($tableData['seats_total'] ?? 0)));
            $primaryDimension = $isChairRow
                ? ProjectTable::chairRowWidth($chairRowSeats)
                : ($isLongTable ? max(100, min(2000, (float) $tableData['primary_dimension'])) : $tableData['primary_dimension']);
            $secondaryDimension = $isChairRow
                ? ProjectTable::CHAIR_ROW_HEIGHT
                : ($isLongTable ? ProjectTable::LONG_TABLE_WIDTH : ($tableData['secondary_dimension'] ?? $tableData['primary_dimension']));

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
                    'curve_count' => $isLongTable ? max(0, min(4, (int) ($tableData['curve_count'] ?? 0))) : null,
                    'curve_type' => $isLongTable
                        ? ($tableData['curve_type'] ?? 'medium')
                        : ($isChairRow ? ($tableData['curve_type'] ?? 'none') : null),
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
        app(SeatingPlanMapRenderer::class)->storePreview($this->currentSeatingPlan->refresh()->load(['tables', 'layoutElements']));
    }

    public function addLayoutElement(string $type, ?string $shape = null): int
    {
        if (! in_array($type, array_keys(ProjectLayoutElement::ELEMENT_TYPE_OPTIONS), true)) {
            $type = 'space';
        }

        $shape = in_array($shape, array_keys(ProjectLayoutElement::SHAPE_OPTIONS), true) ? $shape : 'rectangle';
        $nextOrder = ((int) $this->currentSeatingPlan->layoutElements()->max('sort_order')) + 1;

        $element = $this->currentSeatingPlan->layoutElements()->create([
            'element_type' => $type,
            'shape' => $type === 'space' ? $shape : null,
            'label' => $type === 'text' ? 'Text' : 'Space',
            'center_x' => 700,
            'center_y' => 450,
            'rotation' => 0,
            'width' => $type === 'text' ? 160 : ($shape === 'circle' ? 120 : 220),
            'height' => $type === 'text' ? 48 : ($shape === 'circle' ? 120 : 120),
            'background_color' => $type === 'text' ? '#ffffff' : '#f3eadc',
            'sort_order' => $nextOrder,
        ]);

        $this->currentSeatingPlan->unsetRelation('layoutElements');
        $this->refreshPreviewImage();

        return $element->id;
    }

    #[Renderless]
    public function saveLayoutElements(array $elements): void
    {
        $data = validator(
            ['elements' => $elements],
            [
                'elements' => ['array'],
                'elements.*.id' => ['required', 'integer', 'exists:project_layout_elements,id'],
                'elements.*.element_type' => ['required', 'string', Rule::in(array_keys(ProjectLayoutElement::ELEMENT_TYPE_OPTIONS))],
                'elements.*.shape' => ['nullable', 'string', Rule::in(array_keys(ProjectLayoutElement::SHAPE_OPTIONS))],
                'elements.*.label' => ['nullable', 'string', 'max:255'],
                'elements.*.center_x' => ['required', 'numeric'],
                'elements.*.center_y' => ['required', 'numeric'],
                'elements.*.rotation' => ['required', 'numeric'],
                'elements.*.width' => ['required', 'numeric', 'min:20', 'max:2000'],
                'elements.*.height' => ['required', 'numeric', 'min:20', 'max:2000'],
                'elements.*.background_color' => ['nullable', 'string', 'max:20'],
            ]
        )->validate();

        $allowedIds = $this->currentSeatingPlan->layoutElements()->pluck('id')->all();

        foreach ($data['elements'] as $elementData) {
            if (! in_array((int) $elementData['id'], $allowedIds, true)) {
                continue;
            }

            $isSpace = $elementData['element_type'] === 'space';

            ProjectLayoutElement::query()
                ->whereKey($elementData['id'])
                ->where('project_seating_plan_id', $this->currentSeatingPlan->id)
                ->update([
                    'element_type' => $elementData['element_type'],
                    'shape' => $isSpace ? ($elementData['shape'] ?? 'rectangle') : null,
                    'label' => trim((string) ($elementData['label'] ?? '')),
                    'center_x' => $elementData['center_x'],
                    'center_y' => $elementData['center_y'],
                    'rotation' => $elementData['rotation'],
                    'width' => $elementData['width'],
                    'height' => $elementData['height'],
                    'background_color' => $elementData['background_color'] ?? null,
                ]);
        }

        $this->currentSeatingPlan->unsetRelation('layoutElements');
        $this->refreshPreviewImage();
    }

    public function deleteLayoutElement(int $elementId): void
    {
        $deleted = $this->currentSeatingPlan
            ->layoutElements()
            ->whereKey($elementId)
            ->delete();

        if (! $deleted) {
            return;
        }

        $this->currentSeatingPlan->unsetRelation('layoutElements');
        $this->refreshPreviewImage();

        Notification::make()->title('Layout element deleted')->success()->send();
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
        $isLongTable = $source->table_type === 'long_table';
        $nextCenterX = ($isChairRow || $isLongTable)
            ? (float) $source->center_x
            : min(1350, (float) $source->center_x + (float) $source->primary_dimension + $seatMargin);
        $nextCenterY = $isChairRow
            ? (float) $source->center_y + 58
            : ($isLongTable ? ((float) $source->center_y > 700 ? (float) $source->center_y - 150 : (float) $source->center_y + 150) : (float) $source->center_y);

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
            'curve_count' => $source->curve_count,
            'curve_type' => $source->curve_type,
            'guest_assignments_json' => [],
            'sort_order' => $nextOrder,
        ]);

        $this->currentSeatingPlan->unsetRelation('tables');
        $this->refreshPreviewImage();

        Notification::make()->title('Table duplicated')->success()->send();

        return $table->id;
    }
}
