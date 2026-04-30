<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Project;
use App\Models\ProjectTimeline;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewProjectTimeline extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

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
        'location' => '',
        'supplier_id' => '',
        'title' => '',
        'description' => '',
        'notes' => '',
        'existing_image_paths' => [],
    ];

    public array $timelineImageUploads = [];

    public ?int $confirmDeleteTimelineItemId = null;

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
        $project = $this->getRecord()->loadMissing('projectTimelineItems.supplier');
        $days = $this->getTimelineDays()->map(function (array $day): array {
            return [
                ...$day,
                'items' => $day['items']->map(function (ProjectTimeline $item): array {
                    return [
                        'title' => $item->title,
                        'location' => $item->location,
                        'supplier_name' => $item->supplier?->name,
                        'start_time' => $item->start_time?->format('H:i'),
                        'end_time' => $item->end_time?->format('H:i'),
                        'sunset_time' => $item->sunset_time?->format('H:i'),
                        'description' => $item->description,
                        'notes' => $item->notes,
                        'images' => collect($item->image_paths ?? [])
                            ->map(fn (string $path): ?string => $this->imagePathToDataUri($path))
                            ->filter()
                            ->values()
                            ->all(),
                    ];
                })->all(),
            ];
        });

        $dateRange = $this->getProjectDateRangeLabel($project);
        $location = collect([$project->locality, $project->region])->filter()->implode(', ');
        $partners = collect([$project->partner_one_name, $project->partner_two_name])->filter()->implode(' & ');

        $pdf = Pdf::loadView('filament.resources.project-resource.exports.timeline-pdf', [
            'project' => $project,
            'days' => $days,
            'dateRange' => $dateRange,
            'location' => $location,
            'partners' => $partners,
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

        return [
            'days' => $this->getTimelineDays()->count(),
            'items' => $items->count(),
            'suppliers' => $items->whereNotNull('supplier_id')->pluck('supplier_id')->unique()->count(),
            'notes' => $items->filter(fn (ProjectTimeline $item): bool => filled($item->notes))->count(),
        ];
    }

    public function getTimelineDays(): Collection
    {
        $project = $this->getRecord();
        $start = $project->event_start_date;
        $end = $project->event_end_date ?: $project->event_start_date;

        if (! $start || ! $end) {
            return collect();
        }

        $groupedItems = $project
            ->loadMissing('projectTimelineItems.supplier')
            ->projectTimelineItems
            ->sortBy(fn (ProjectTimeline $item): string => sprintf(
                '%s-%s-%05d',
                $item->timeline_date?->format('Ymd') ?? '99999999',
                $item->start_time?->format('H:i') ?? '99:99',
                $item->sort_order,
            ))
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

    public function startCreateTimelineItem(?string $date = null): void
    {
        $this->editingTimelineItemId = null;
        $this->resetTimelineForm($date ?: $this->getRecord()->event_start_date?->format('Y-m-d'));
        $this->showTimelineEditor = true;
    }

    public function editTimelineItem(int $itemId): void
    {
        $item = $this->findTimelineItem($itemId);
        $this->editingTimelineItemId = $item->id;
        $this->timelineImageUploads = [];
        $this->showTimelineEditor = true;

        $this->timelineForm = [
            'timeline_date' => $item->timeline_date?->format('Y-m-d') ?? '',
            'start_time' => $item->start_time?->format('H:i') ?? '',
            'end_time' => $item->end_time?->format('H:i') ?? '',
            'sunset_time' => $item->sunset_time?->format('H:i') ?? '',
            'location' => $item->location ?? '',
            'supplier_id' => $item->supplier_id ?? '',
            'title' => $item->title ?? '',
            'description' => $item->description ?? '',
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
                'form.location' => ['nullable', 'string', 'max:255'],
                'form.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'form.title' => ['required', 'string', 'max:255'],
                'form.description' => ['nullable', 'string'],
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
            'location' => $data['form']['location'] ?: null,
            'supplier_id' => $data['form']['supplier_id'] ?: null,
            'title' => trim((string) $data['form']['title']),
            'description' => filled($data['form']['description'] ?? null) ? trim((string) $data['form']['description']) : null,
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
        $this->confirmDeleteTimelineItemId = $itemId;
    }

    public function cancelDeleteTimelineItem(): void
    {
        $this->confirmDeleteTimelineItemId = null;
    }

    public function confirmDeleteTimelineItem(): void
    {
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

    protected function resetTimelineForm(?string $date = null): void
    {
        $defaultDate = $date ?: $this->getRecord()->event_start_date?->format('Y-m-d') ?? '';

        $this->timelineForm = [
            'timeline_date' => $defaultDate,
            'start_time' => '',
            'end_time' => '',
            'sunset_time' => '',
            'location' => '',
            'supplier_id' => '',
            'title' => '',
            'description' => '',
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
