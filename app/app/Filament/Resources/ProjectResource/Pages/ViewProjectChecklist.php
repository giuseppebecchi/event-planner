<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Checklist;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ViewProjectChecklist extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-checklist';

    protected static ?string $breadcrumb = 'Checklist';

    protected Width|string|null $maxContentWidth = Width::Full;

    public array $checklistForms = [];
    public bool $hideCompleted = false;
    public ?int $expandedChecklistItemId = null;
    public ?int $confirmDeleteChecklistItemId = null;
    public ?int $pinnedChecklistItemId = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        if ($this->getRecord()->projectChecklistOptions()->doesntExist()) {
            $this->getRecord()->syncChecklistOptionsFromTemplates();
            $this->getRecord()->refresh();
        }

        $this->loadChecklistForms();
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

    public function getChecklistSummary(): array
    {
        $items = $this->getManagedChecklistItems();
        $today = now()->startOfDay();
        $dueSoonLimit = now()->addDays(30)->startOfDay();

        return [
            'sections' => $this->getChecklistSections()->count(),
            'total' => $items->count(),
            'completed' => $items->where('completed', true)->count(),
            'open' => $items->where('completed', false)->count(),
            'due_soon' => $items
                ->where('completed', false)
                ->filter(fn (ProjectChecklistOption $item): bool => $item->due_date !== null && $item->due_date->between($today, $dueSoonLimit))
                ->count(),
        ];
    }

    public function getChecklistSections(): Collection
    {
        $items = $this->getManagedChecklistItems();
        $record = $this->getRecord();
        $clientLabel = collect([$record->partner_one_name, $record->partner_two_name])->filter()->implode(' & ');

        $sections = collect([
            [
                'key' => 'admin',
                'title' => 'ME',
                'subtitle' => 'planner',
                'avatar' => 'GB',
                'items' => $items->where('assigned_to', 'admin')->values(),
            ],
            [
                'key' => 'client',
                'title' => $clientLabel !== '' ? mb_strtoupper($clientLabel) : 'CLIENT',
                'subtitle' => 'client',
                'avatar' => $this->getInitials($clientLabel !== '' ? $clientLabel : 'Client'),
                'items' => $items->where('assigned_to', 'client')->values(),
            ],
        ]);

        $supplierSections = $items
            ->where('assigned_to', 'supplier')
            ->groupBy(fn (ProjectChecklistOption $item): string => $item->supplier_id ? 'supplier-' . $item->supplier_id : 'supplier-unassigned')
            ->map(function (Collection $supplierItems, string $key): array {
                /** @var ProjectChecklistOption $first */
                $first = $supplierItems->first();
                $supplier = $first->supplier;
                $title = $supplier?->name ? mb_strtoupper($supplier->name) : 'SUPPLIER TO ASSIGN';
                $subtitle = $supplier?->category?->label_it ?? ($supplier?->category?->label ?? 'supplier');

                return [
                    'key' => $key,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'avatar' => $this->getInitials($supplier?->name ?? 'Supplier'),
                    'items' => $supplierItems->values(),
                ];
            })
            ->sortBy(fn (array $section): string => mb_strtolower($section['title']))
            ->values();

        return $sections
            ->concat($supplierSections)
            ->map(function (array $section): array {
                /** @var Collection<int, ProjectChecklistOption> $sectionItems */
                $sectionItems = $section['items'];
                $visibleItems = $this->hideCompleted
                    ? $sectionItems->where('completed', false)->values()
                    : $sectionItems->values();

                return [
                    ...$section,
                    'items' => $visibleItems->sortBy(fn (ProjectChecklistOption $item): string => sprintf(
                        '%d-%s-%05d',
                        $this->pinnedChecklistItemId === $item->id ? 0 : 1,
                        $item->due_date?->format('Ymd') ?? '99999999',
                        $item->order,
                    ))->values(),
                    'count' => $visibleItems->count(),
                    'total_count' => $sectionItems->count(),
                ];
            });
    }

    public function saveChecklistItem(int $itemId): void
    {
        $item = $this->findChecklistItem($itemId);
        $data = $this->checklistForms[$itemId] ?? [];

        validator($data, [
            'title' => ['nullable', 'string'],
            'details' => ['nullable', 'string'],
        ])->validate();

        $item->forceFill([
            'title' => trim((string) ($data['title'] ?? '')),
            'details' => filled($data['details'] ?? null) ? trim((string) $data['details']) : null,
        ])->save();

        $this->syncChecklistForm($item->fresh());
    }

    public function updatedChecklistForms(mixed $value, string $name): void
    {
        if (! preg_match('/^(\d+)\.(title|details|anticipation_value|anticipation_unit|exact_due_date)$/', $name, $matches)) {
            return;
        }

        if (in_array($matches[2], ['title', 'details'], true)) {
            $this->saveChecklistItem((int) $matches[1]);

            return;
        }

        $this->saveChecklistSchedule((int) $matches[1]);
    }

    public function toggleChecklistCompleted(int $itemId, bool $completed): void
    {
        $item = $this->findChecklistItem($itemId);

        $item->forceFill([
            'completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ])->save();

        $this->syncChecklistForm($item->fresh());
    }

    public function addChecklistItem(string $assignedTo, ?int $supplierId = null): void
    {
        $assignedTo = ProjectChecklistOption::normalizeAssignedTo($assignedTo);

        $customChecklist = Checklist::query()->firstOrCreate(
            ['title' => 'Custom checklist'],
            ['category_id' => null, 'options' => []],
        );

        $nextOrder = ((int) $this->getRecord()->projectChecklistOptions()
            ->where('checkbox_id', $customChecklist->id)
            ->max('order')) + 1;

        $item = $this->getRecord()->projectChecklistOptions()->create([
            'supplier_id' => $assignedTo === 'supplier' ? $supplierId : null,
            'category_budget_id' => null,
            'checkbox_id' => $customChecklist->id,
            'order' => $nextOrder > 0 ? $nextOrder : 1,
            'title' => '',
            'details' => null,
            'default' => false,
            'anticipation' => null,
            'assigned_to' => $assignedTo,
            'due_date' => null,
            'enabled' => true,
            'completed' => false,
            'completed_at' => null,
        ]);

        $this->reloadChecklistState();
        $this->syncChecklistForm($item->fresh());
        $this->expandedChecklistItemId = $item->id;
        $this->pinnedChecklistItemId = $item->id;
    }

    public function saveChecklistSchedule(int $itemId): void
    {
        $item = $this->findChecklistItem($itemId);
        $data = $this->checklistForms[$itemId] ?? [];
        $mode = $data['due_date_mode'] ?? 'relative';

        if ($mode === 'exact') {
            $exactDueDate = filled($data['exact_due_date'] ?? null)
                ? Carbon::parse((string) $data['exact_due_date'])->startOfDay()
                : null;

            $item->forceFill([
                'anticipation' => null,
                'due_date' => $exactDueDate,
            ])->save();

            $this->syncChecklistForm($item->fresh());

            return;
        }

        $value = isset($data['anticipation_value']) && $data['anticipation_value'] !== ''
            ? (int) $data['anticipation_value']
            : null;
        $unit = in_array(($data['anticipation_unit'] ?? null), ['days', 'weeks', 'months'], true)
            ? (string) $data['anticipation_unit']
            : null;

        $anticipation = ($value && $value > 0 && $unit)
            ? $value . ' ' . $unit
            : null;

        $item->forceFill([
            'anticipation' => $anticipation,
            'due_date' => Project::calculateChecklistDueDate($this->getRecord()->event_start_date, $anticipation),
        ])->save();

        $this->syncChecklistForm($item->fresh());
    }

    public function promptDeleteChecklistItem(int $itemId): void
    {
        $this->confirmDeleteChecklistItemId = $itemId;
    }

    public function cancelDeleteChecklistItem(): void
    {
        $this->confirmDeleteChecklistItemId = null;
    }

    public function confirmDeleteChecklistItem(): void
    {
        if (! $this->confirmDeleteChecklistItemId) {
            return;
        }

        $itemId = $this->confirmDeleteChecklistItemId;
        $item = $this->findChecklistItem($itemId);

        unset($this->checklistForms[$itemId]);
        $item->delete();

        if ($this->expandedChecklistItemId === $itemId) {
            $this->expandedChecklistItemId = null;
        }

        $this->confirmDeleteChecklistItemId = null;
        $this->reloadChecklistState();

        Notification::make()
            ->title('Task deleted')
            ->success()
            ->send();
    }

    public function expandChecklistItem(int $itemId): void
    {
        $this->expandedChecklistItemId = $itemId;
    }

    public function collapseChecklistItem(): void
    {
        if ($this->expandedChecklistItemId) {
            $this->saveChecklistSchedule($this->expandedChecklistItemId);
        }

        if ($this->pinnedChecklistItemId === $this->expandedChecklistItemId) {
            $this->pinnedChecklistItemId = null;
        }

        $this->expandedChecklistItemId = null;
    }

    protected function getManagedChecklistItems(): Collection
    {
        return $this->getRecord()
            ->loadMissing([
                'projectChecklistOptions.checklist.category',
                'projectChecklistOptions.supplier.category',
            ])
            ->projectChecklistOptions
            ->where('enabled', true)
            ->values();
    }

    protected function findChecklistItem(int $itemId): ProjectChecklistOption
    {
        /** @var ProjectChecklistOption $item */
        $item = $this->getRecord()
            ->projectChecklistOptions()
            ->with(['checklist', 'supplier.category'])
            ->findOrFail($itemId);

        return $item;
    }

    protected function reloadChecklistState(): void
    {
        $this->getRecord()->unsetRelation('projectChecklistOptions');
        $this->getRecord()->refresh();
        $this->loadChecklistForms();
    }

    protected function loadChecklistForms(): void
    {
        $this->checklistForms = $this->getRecord()
            ->projectChecklistOptions()
            ->get()
            ->mapWithKeys(fn (ProjectChecklistOption $item): array => [$item->id => $this->makeChecklistFormState($item)])
            ->all();
    }

    protected function syncChecklistForm(ProjectChecklistOption $item): void
    {
        $this->checklistForms[$item->id] = $this->makeChecklistFormState($item);

        $this->getRecord()->unsetRelation('projectChecklistOptions');
    }

    protected function makeChecklistFormState(ProjectChecklistOption $item): array
    {
        [$anticipationValue, $anticipationUnit] = $this->splitAnticipation($item->anticipation);

        return [
            'title' => $item->title ?? '',
            'details' => $item->details ?? '',
            'completed' => (bool) $item->completed,
            'due_date_mode' => $item->anticipation ? 'relative' : 'exact',
            'anticipation_value' => $anticipationValue,
            'anticipation_unit' => $anticipationUnit ?? 'weeks',
            'exact_due_date' => $item->due_date?->format('Y-m-d') ?? '',
        ];
    }

    protected function splitAnticipation(?string $anticipation): array
    {
        if (! $anticipation) {
            return ['', null];
        }

        $parts = preg_split('/\s+/', trim($anticipation), 2);

        if (! is_array($parts) || count($parts) < 2) {
            return ['', null];
        }

        return [
            is_numeric($parts[0]) ? (string) ((int) $parts[0]) : '',
            trim((string) $parts[1]),
        ];
    }

    protected function getInitials(string $label): string
    {
        $parts = collect(preg_split('/\s+/', trim($label)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)));

        return $parts->implode('') ?: 'GB';
    }
}
