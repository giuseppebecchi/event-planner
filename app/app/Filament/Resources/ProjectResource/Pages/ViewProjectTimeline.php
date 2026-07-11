<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use App\Models\ProjectTimeline;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewProjectTimeline extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

    protected const DAILY_NOTES_TITLE = 'Daily notes';

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-timeline';

    protected static ?string $breadcrumb = 'Timeline';

    protected Width|string|null $maxContentWidth = Width::Full;

    public bool $showTimelineEditor = false;

    public ?int $editingTimelineItemId = null;

    public array $timelineForm = [
        'timeline_date' => '',
        'start_time' => '',
        'end_time' => '',
        'sunset_time' => '',
        'is_surprise' => false,
        'cover_activity' => false,
        'cover_activity_type' => '',
        'location' => '',
        'location_plan_b' => '',
        'supplier_id' => '',
        'title' => '',
        'description' => '',
        'has_extended_description' => false,
        'extended_description' => '',
        'notes' => '',
        'existing_image_paths' => [],
    ];

    public array $timelineImageUploads = [];

    public ?int $confirmDeleteTimelineItemId = null;

    public ?string $editingDailyNoteDate = null;

    public array $dailyNoteForms = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->resetTimelineForm($this->getRecord()->event_start_date?->format('Y-m-d'));
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

    public function exportTimelinePdf(): StreamedResponse
    {
        $project = $this->getRecord()->loadMissing(
            'projectTimelineItems.supplier',
            'categoryBudgetSuppliers.supplier.category',
            'categoryBudgetSuppliers.category'
        );
        $days = $this->getTimelineDays()->map(function (array $day) use ($project): array {
            $items = $day['items']->map(function (ProjectTimeline $item) use ($project): array {
                return [
                    'title' => $item->title,
                    'date' => $item->timeline_date?->format('F j, Y'),
                    'location' => $item->location,
                    'supplier_name' => $item->supplier?->name,
                    'start_time' => $project->formatTimeForDisplay($item->start_time),
                    'end_time' => $project->formatTimeForDisplay($item->end_time),
                    'sunset_time' => $project->formatTimeForDisplay($item->sunset_time),
                    'is_surprise' => (bool) $item->is_surprise,
                    'cover_activity' => (bool) $item->cover_activity,
                    'cover_activity_type' => $item->cover_activity_type,
                    'description' => $item->description,
                    'location_plan_b' => $item->location_plan_b,
                    'has_extended_description' => (bool) $item->has_extended_description,
                    'extended_description' => $item->extended_description,
                    'notes' => $item->notes,
                    'images' => collect($item->image_paths ?? [])
                        ->map(fn (string $path): ?string => $this->imagePathToDataUri($path))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })->all();

            return [
                ...$day,
                'sunset_time' => $project->formatTimeForDisplay($day['sunset_time']),
                'daily_note_description' => $day['daily_note']?->description,
                'items' => $items,
                'extended_items' => collect($items)
                    ->filter(fn (array $item): bool => (bool) $item['has_extended_description'] && filled($item['extended_description']))
                    ->values()
                    ->all(),
            ];
        });
        $timelineItems = $project->projectTimelineItems
            ->reject(fn (ProjectTimeline $item): bool => $this->isDailyNoteItem($item))
            ->when(auth()->user()?->isCustomer(), fn (Collection $items): Collection => $items->reject(fn (ProjectTimeline $item): bool => (bool) $item->is_surprise))
            ->sortBy(fn (ProjectTimeline $item): string => sprintf(
                '%s-%s-%05d',
                $item->timeline_date?->format('Ymd') ?? '99999999',
                $item->start_time?->format('H:i') ?? '99:99',
                $item->sort_order,
            ))
            ->values();
        $coverActivities = $timelineItems
            ->filter(fn (ProjectTimeline $item): bool => (bool) $item->cover_activity)
            ->map(fn (ProjectTimeline $item): array => $this->timelineItemPdfPayload($item) + [
                'icon' => $this->coverActivityIconDataUri($item->cover_activity_type),
            ])
            ->values();
        $confirmedSuppliers = $project->categoryBudgetSuppliers
            ->filter(fn (CategoryBudgetSupplier $proposal): bool => $proposal->proposal_status === CategoryBudgetSupplier::STATUS_CONFIRMED && $proposal->supplier)
            ->sortBy(fn (CategoryBudgetSupplier $proposal): string => sprintf(
                '%s-%s',
                $proposal->category?->label ?? $proposal->supplier?->category?->label ?? '',
                $proposal->supplier?->name ?? ''
            ))
            ->map(fn (CategoryBudgetSupplier $proposal): array => $this->confirmedSupplierPdfPayload($proposal))
            ->values();

        $dateRange = $this->getProjectDateRangeLabel($project);
        $location = collect([$project->locality, $project->region])->filter()->implode(', ');
        $partners = $project->coupleNames();
        $coverImage = filled($project->cover_image_path)
            ? $this->imagePathToDataUri($project->cover_image_path)
            : null;
        $leftRailImage = filled($project->recap_left_rail_image_path)
            ? $this->imagePathToDataUri($project->recap_left_rail_image_path)
            : $this->localFileToDataUri(public_path('images/pdf/timeline-left-rail.png'));
        $seatingPlans = method_exists($this, 'recapSeatingPlanPdfItems')
            ? $this->recapSeatingPlanPdfItems()
            : collect();

        $pdf = Pdf::loadView('filament.resources.project-resource.exports.timeline-pdf', [
            'project' => $project,
            'days' => $days,
            'coverActivities' => $coverActivities,
            'confirmedSuppliers' => $confirmedSuppliers,
            'dateRange' => $dateRange,
            'location' => $location,
            'partners' => $partners,
            'coverImage' => $coverImage,
            'leftRailImage' => $leftRailImage,
            'recapChecklistItems' => $this->getRecapChecklistPdfItems(),
            'seatingPlans' => $seatingPlans,
            'generatedAt' => now()->format('F j, Y'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            sprintf('%s-timeline.pdf', str($project->name)->slug()->value() ?: 'project-timeline'),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function getTimelineSummary(): array
    {
        $items = $this->getRecord()->loadMissing('projectTimelineItems')->projectTimelineItems;
        $timelineItems = $items->reject(fn (ProjectTimeline $item): bool => $this->isDailyNoteItem($item));

        return [
            'days' => $this->getTimelineDays()->count(),
            'items' => $timelineItems->count(),
            'suppliers' => $timelineItems->whereNotNull('supplier_id')->pluck('supplier_id')->unique()->count(),
            'notes' => $items->filter(fn (ProjectTimeline $item): bool => filled($item->notes) || $this->isDailyNoteItem($item))->count(),
        ];
    }

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

    protected function getRecapChecklistPdfItems(): Collection
    {
        return $this->getRecapChecklistItems()
            ->map(fn (ProjectChecklistOption $item): array => [
                'title' => $item->title ?: 'Checklist item',
                'response' => $item->response,
                'details' => $item->details,
                'supplier_name' => $item->supplier?->name,
                'due_date' => $item->due_date?->format('F j, Y'),
            ]);
    }

    public function getTimelineDays(): Collection
    {
        $project = $this->getRecord();
        $start = $project->event_start_date;
        $end = $project->event_end_date ?: $project->event_start_date;

        if (! $start || ! $end) {
            return collect();
        }

        $allItems = $project
            ->loadMissing('projectTimelineItems.supplier')
            ->projectTimelineItems
            ->sortBy(fn (ProjectTimeline $item): string => sprintf(
                '%s-%s-%05d',
                $item->timeline_date?->format('Ymd') ?? '99999999',
                $item->start_time?->format('H:i') ?? '99:99',
                $item->sort_order,
            ));

        $dailyNotes = $allItems
            ->filter(fn (ProjectTimeline $item): bool => $this->isDailyNoteItem($item))
            ->keyBy(fn (ProjectTimeline $item): string => $item->timeline_date->format('Y-m-d'));

        $groupedItems = $allItems
            ->reject(fn (ProjectTimeline $item): bool => $this->isDailyNoteItem($item))
            ->when(auth()->user()?->isCustomer(), fn (Collection $items): Collection => $items->reject(fn (ProjectTimeline $item): bool => (bool) $item->is_surprise))
            ->groupBy(fn (ProjectTimeline $item): string => $item->timeline_date->format('Y-m-d'));

        $days = collect();
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $key = $cursor->format('Y-m-d');
            $items = ($groupedItems->get($key) ?? collect())->values();

            $days->push([
                'date' => $cursor->copy(),
                'key' => $key,
                'sunset_time' => $items->first(fn (ProjectTimeline $item): bool => $item->sunset_time !== null)?->sunset_time,
                'daily_note' => auth()->user()?->isCustomer() ? null : $dailyNotes->get($key),
                'items' => $items,
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    public function getSupplierOptions(): array
    {
        return $this->getRecord()
            ->loadMissing('categoryBudgetSuppliers.supplier')
            ->categoryBudgetSuppliers
            ->map(fn ($proposal) => $proposal->supplier)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->mapWithKeys(fn ($supplier): array => [$supplier->id => $supplier->name])
            ->all();
    }

    public function startEditDailyNotes(string $date): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $dailyNote = $this->findDailyNoteForDate($date);

        $this->editingDailyNoteDate = $date;
        $this->dailyNoteForms[$date] = [
            'description' => $dailyNote?->description ?? '',
        ];
    }

    public function cancelEditDailyNotes(): void
    {
        $this->editingDailyNoteDate = null;
    }

    public function saveDailyNotes(string $date): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $data = validator(
            [
                'date' => $date,
                'description' => $this->dailyNoteForms[$date]['description'] ?? null,
            ],
            [
                'date' => ['required', 'date'],
                'description' => ['required', 'string'],
            ]
        )->validate();

        if (! $this->dateBelongsToProjectRange($data['date'])) {
            Notification::make()
                ->title('Daily notes date is outside the project dates')
                ->danger()
                ->send();

            return;
        }

        $description = trim((string) $data['description']);

        $dailyNote = $this->findDailyNoteForDate($data['date']);
        $payload = [
            'timeline_date' => $data['date'],
            'start_time' => null,
            'end_time' => null,
            'sunset_time' => null,
            'is_surprise' => false,
            'cover_activity' => false,
            'cover_activity_type' => null,
            'location' => null,
            'location_plan_b' => null,
            'supplier_id' => null,
            'title' => self::DAILY_NOTES_TITLE,
            'description' => $description,
            'has_extended_description' => false,
            'extended_description' => null,
            'notes' => null,
            'image_paths' => [],
            'sort_order' => 0,
        ];

        if ($dailyNote) {
            $dailyNote->update($payload);
        } else {
            $this->getRecord()->projectTimelineItems()->create($payload);
        }

        $this->editingDailyNoteDate = null;
        $this->getRecord()->unsetRelation('projectTimelineItems');

        Notification::make()
            ->title('Daily notes saved')
            ->success()
            ->send();
    }

    public function startCreateTimelineItem(?string $date = null): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $this->editingTimelineItemId = null;
        $this->resetTimelineForm($date ?: $this->getRecord()->event_start_date?->format('Y-m-d'));
        $this->showTimelineEditor = true;
    }

    public function editTimelineItem(int $itemId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $item = $this->findTimelineItem($itemId);
        $this->editingTimelineItemId = $item->id;
        $this->timelineImageUploads = [];
        $this->showTimelineEditor = true;

        $this->timelineForm = [
            'timeline_date' => $item->timeline_date?->format('Y-m-d') ?? '',
            'start_time' => $item->start_time?->format('H:i') ?? '',
            'end_time' => $item->end_time?->format('H:i') ?? '',
            'sunset_time' => $item->sunset_time?->format('H:i') ?? '',
            'is_surprise' => (bool) $item->is_surprise,
            'cover_activity' => (bool) $item->cover_activity,
            'cover_activity_type' => $item->cover_activity_type ?? '',
            'location' => $item->location ?? '',
            'location_plan_b' => $item->location_plan_b ?? '',
            'supplier_id' => $item->supplier_id ?? '',
            'title' => $item->title ?? '',
            'description' => $item->description ?? '',
            'has_extended_description' => (bool) $item->has_extended_description,
            'extended_description' => $item->extended_description ?? '',
            'notes' => $item->notes ?? '',
            'existing_image_paths' => array_values($item->image_paths ?? []),
        ];
    }

    public function closeTimelineEditor(): void
    {
        $this->showTimelineEditor = false;
        $this->editingTimelineItemId = null;
        $this->resetTimelineForm($this->getRecord()->event_start_date?->format('Y-m-d'));
    }

    public function removeTimelineImage(int $index): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        if (! array_key_exists($index, $this->timelineForm['existing_image_paths'])) {
            return;
        }

        $path = $this->timelineForm['existing_image_paths'][$index];

        if (is_string($path) && $path !== '') {
            Storage::disk('public')->delete($path);
        }

        unset($this->timelineForm['existing_image_paths'][$index]);
        $this->timelineForm['existing_image_paths'] = array_values($this->timelineForm['existing_image_paths']);
    }

    public function saveTimelineItem(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $project = $this->getRecord();
        $projectStart = $project->event_start_date?->copy()?->startOfDay();
        $projectEnd = ($project->event_end_date ?: $project->event_start_date)?->copy()?->startOfDay();

        $data = validator(
            [
                'form' => $this->timelineForm,
                'uploads' => $this->timelineImageUploads,
            ],
            [
                'form.timeline_date' => ['required', 'date'],
                'form.start_time' => ['nullable', 'date_format:H:i'],
                'form.end_time' => ['nullable', 'date_format:H:i'],
                'form.sunset_time' => ['nullable', 'date_format:H:i'],
                'form.is_surprise' => ['boolean'],
                'form.cover_activity' => ['boolean'],
                'form.cover_activity_type' => [
                    Rule::requiredIf((bool) ($this->timelineForm['cover_activity'] ?? false)),
                    'nullable',
                    'string',
                    Rule::in(array_keys($this->getCoverActivityTypeOptions())),
                ],
                'form.location' => ['nullable', 'string', 'max:255'],
                'form.location_plan_b' => ['nullable', 'string', 'max:255'],
                'form.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'form.title' => ['required', 'string', 'max:255'],
                'form.description' => ['nullable', 'string'],
                'form.has_extended_description' => ['boolean'],
                'form.extended_description' => [
                    Rule::requiredIf((bool) ($this->timelineForm['has_extended_description'] ?? false)),
                    'nullable',
                    'string',
                ],
                'form.notes' => ['nullable', 'string'],
                'form.existing_image_paths' => ['array'],
                'form.existing_image_paths.*' => ['string'],
                'uploads' => ['array'],
                'uploads.*' => ['image', 'max:20480'],
            ]
        )->validate();

        if (
            $projectStart
            && $projectEnd
            && filled($data['form']['timeline_date'] ?? null)
        ) {
            $timelineDate = \Illuminate\Support\Carbon::parse($data['form']['timeline_date'])->startOfDay();

            if ($timelineDate->lt($projectStart) || $timelineDate->gt($projectEnd)) {
                $this->addError('timelineForm.timeline_date', sprintf(
                    'Timeline date must be between %s and %s.',
                    $projectStart->format('Y-m-d'),
                    $projectEnd->format('Y-m-d'),
                ));

                return;
            }
        }

        $storedUploads = collect($this->timelineImageUploads)
            ->map(fn ($upload): string => $upload->store('projects/timeline', 'public'))
            ->all();

        $wasEditing = (bool) $this->editingTimelineItemId;

        $payload = [
            'timeline_date' => $data['form']['timeline_date'],
            'start_time' => $data['form']['start_time'] ?: null,
            'end_time' => $data['form']['end_time'] ?: null,
            'sunset_time' => $data['form']['sunset_time'] ?: null,
            'is_surprise' => (bool) ($data['form']['is_surprise'] ?? false),
            'cover_activity' => (bool) ($data['form']['cover_activity'] ?? false),
            'cover_activity_type' => (bool) ($data['form']['cover_activity'] ?? false)
                ? ($data['form']['cover_activity_type'] ?: null)
                : null,
            'location' => $data['form']['location'] ?: null,
            'location_plan_b' => $data['form']['location_plan_b'] ?: null,
            'supplier_id' => $data['form']['supplier_id'] ?: null,
            'title' => trim((string) $data['form']['title']),
            'description' => filled($data['form']['description'] ?? null) ? trim((string) $data['form']['description']) : null,
            'has_extended_description' => (bool) ($data['form']['has_extended_description'] ?? false),
            'extended_description' => (bool) ($data['form']['has_extended_description'] ?? false)
                && filled($data['form']['extended_description'] ?? null)
                    ? trim((string) $data['form']['extended_description'])
                    : null,
            'notes' => filled($data['form']['notes'] ?? null) ? trim((string) $data['form']['notes']) : null,
            'image_paths' => array_values(array_merge($data['form']['existing_image_paths'] ?? [], $storedUploads)),
        ];

        if ($wasEditing) {
            $item = $this->findTimelineItem($this->editingTimelineItemId);
            $item->update($payload);
        } else {
            $sortOrder = ((int) $project->projectTimelineItems()
                ->whereDate('timeline_date', $payload['timeline_date'])
                ->max('sort_order')) + 1;

            $project->projectTimelineItems()->create([
                ...$payload,
                'sort_order' => $sortOrder,
            ]);
        }

        $project->unsetRelation('projectTimelineItems');
        $this->showTimelineEditor = false;
        $this->editingTimelineItemId = null;
        $this->resetTimelineForm($payload['timeline_date']);

        Notification::make()
            ->title($wasEditing ? 'Timeline item updated' : 'Timeline item created')
            ->success()
            ->send();
    }

    public function promptDeleteTimelineItem(int $itemId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        $this->confirmDeleteTimelineItemId = $itemId;
    }

    public function cancelDeleteTimelineItem(): void
    {
        $this->confirmDeleteTimelineItemId = null;
    }

    public function confirmDeleteTimelineItem(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        if (! $this->confirmDeleteTimelineItemId) {
            return;
        }

        $item = $this->findTimelineItem($this->confirmDeleteTimelineItemId);

        foreach ($item->image_paths ?? [] as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }

        $item->delete();

        if ($this->editingTimelineItemId === $this->confirmDeleteTimelineItemId) {
            $this->closeTimelineEditor();
        }

        $this->confirmDeleteTimelineItemId = null;
        $this->getRecord()->unsetRelation('projectTimelineItems');

        Notification::make()
            ->title('Timeline item deleted')
            ->success()
            ->send();
    }

    protected function findTimelineItem(int $itemId): ProjectTimeline
    {
        /** @var ProjectTimeline $item */
        $item = $this->getRecord()
            ->projectTimelineItems()
            ->with('supplier')
            ->findOrFail($itemId);

        return $item;
    }

    protected function findDailyNoteForDate(string $date): ?ProjectTimeline
    {
        return $this->getRecord()
            ->projectTimelineItems()
            ->whereDate('timeline_date', $date)
            ->whereNull('start_time')
            ->where('title', self::DAILY_NOTES_TITLE)
            ->first();
    }

    protected function isDailyNoteItem(ProjectTimeline $item): bool
    {
        return $item->start_time === null && $item->title === self::DAILY_NOTES_TITLE;
    }

    protected function dateBelongsToProjectRange(string $date): bool
    {
        $project = $this->getRecord();
        $projectStart = $project->event_start_date?->copy()?->startOfDay();
        $projectEnd = ($project->event_end_date ?: $project->event_start_date)?->copy()?->startOfDay();

        if (! $projectStart || ! $projectEnd) {
            return true;
        }

        $timelineDate = \Illuminate\Support\Carbon::parse($date)->startOfDay();

        return $timelineDate->between($projectStart, $projectEnd, true);
    }

    protected function resetTimelineForm(?string $date = null): void
    {
        $defaultDate = $date ?: $this->getRecord()->event_start_date?->format('Y-m-d') ?? '';

        $this->timelineForm = [
            'timeline_date' => $defaultDate,
            'start_time' => '',
            'end_time' => '',
            'sunset_time' => '',
            'is_surprise' => false,
            'cover_activity' => false,
            'cover_activity_type' => '',
            'location' => '',
            'location_plan_b' => '',
            'supplier_id' => '',
            'title' => '',
            'description' => '',
            'has_extended_description' => false,
            'extended_description' => '',
            'notes' => '',
            'existing_image_paths' => [],
        ];

        $this->timelineImageUploads = [];
    }

    protected function getProjectDateRangeLabel(Project $project): string
    {
        if (! $project->event_start_date) {
            return 'Date to be defined';
        }

        if (! $project->event_end_date || $project->event_end_date->isSameDay($project->event_start_date)) {
            return $project->event_start_date->format('F j, Y');
        }

        return sprintf(
            '%s - %s',
            $project->event_start_date->format('F j, Y'),
            $project->event_end_date->format('F j, Y'),
        );
    }

    public function getCoverActivityTypeOptions(): array
    {
        return config('timeline.cover_activity_types', []);
    }

    protected function timelineItemPdfPayload(ProjectTimeline $item): array
    {
        $project = $this->getRecord();

        return [
            'title' => $item->title,
            'date' => $item->timeline_date?->format('F j, Y'),
            'location' => $item->location,
            'location_plan_b' => $item->location_plan_b,
            'supplier_name' => $item->supplier?->name,
            'start_time' => $project->formatTimeForDisplay($item->start_time),
            'end_time' => $project->formatTimeForDisplay($item->end_time),
            'sunset_time' => $project->formatTimeForDisplay($item->sunset_time),
            'is_surprise' => (bool) $item->is_surprise,
            'cover_activity' => (bool) $item->cover_activity,
            'cover_activity_type' => $item->cover_activity_type,
            'description' => $item->description,
            'has_extended_description' => (bool) $item->has_extended_description,
            'extended_description' => $item->extended_description,
            'notes' => $item->notes,
        ];
    }

    protected function confirmedSupplierPdfPayload(CategoryBudgetSupplier $proposal): array
    {
        $supplier = $proposal->supplier;

        return [
            'category' => $proposal->category?->label ?? $supplier?->category?->label ?? 'Supplier',
            'name' => $supplier?->name,
            'contact_person' => $supplier?->contact_person,
            'email' => $supplier?->email,
            'phone' => $supplier?->phone,
            'website' => $supplier?->loc_website,
            'address' => collect([
                $supplier?->address_line_1,
                $supplier?->address_line_2,
                $supplier?->postal_code,
                $supplier?->city,
                $supplier?->province,
                $supplier?->region,
                $supplier?->country,
            ])->filter()->implode(', '),
            'notes' => $proposal->notes,
            'confirmed_at' => $proposal->confirmed_at?->format('F j, Y'),
        ];
    }

    protected function coverActivityIconDataUri(?string $type): ?string
    {
        $filename = match ($type) {
            'CEREMONY' => 'ceremony.png',
            'PHOTOS' => 'photos.png',
            'APERITIVO' => 'aperitivo.png',
            'DINNER' => 'dinner.png',
            'CAKE CUTTING' => 'cake-cutting.png',
            'FIRST DANCE' => 'first-dance.png',
            'SEND OFF' => 'send-off.png',
            default => null,
        };

        if (! $filename) {
            return null;
        }

        return $this->localFileToDataUri(public_path('images/timeline-icons/' . $filename));
    }

    protected function localFileToDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mimeType = mime_content_type($path) ?: 'image/png';
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    protected function imagePathToDataUri(string $path): ?string
    {
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);
        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }
}
