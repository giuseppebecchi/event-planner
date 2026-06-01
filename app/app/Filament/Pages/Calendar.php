<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use App\Models\ProjectEvent;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Calendar extends Page
{
    protected static bool $shouldRegisterNavigation = true;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Calendar';

    protected static ?string $slug = 'calendar';

    protected string $view = 'filament.pages.calendar';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $calendarView = 'month';

    public string $visibleMonth = '';

    public ?string $selectedCalendarItemKind = null;

    public ?int $selectedCalendarItemId = null;

    public ?int $editingProjectEventId = null;

    public array $monthPickerForm = [
        'month' => '',
        'year' => '',
    ];

    public array $editEventForm = [
        'title' => '',
        'description' => '',
        'is_multi_day' => false,
        'is_all_day' => true,
        'start_date' => '',
        'end_date' => '',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'include_program' => false,
        'program_html' => '',
    ];

    protected ?Collection $timelineItemsCache = null;

    public function mount(): void
    {
        $this->visibleMonth = now()->startOfMonth()->format('Y-m');
        $this->syncMonthPickerForm();
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function previousMonth(): void
    {
        $this->visibleMonth = $this->visibleMonthDate()->subMonthNoOverflow()->format('Y-m');
        $this->syncMonthPickerForm();
    }

    public function nextMonth(): void
    {
        $this->visibleMonth = $this->visibleMonthDate()->addMonthNoOverflow()->format('Y-m');
        $this->syncMonthPickerForm();
    }

    public function setCalendarView(string $view): void
    {
        if (! in_array($view, ['month', 'list'], true)) {
            return;
        }

        $this->calendarView = $view;
    }

    public function goToSelectedMonth(): void
    {
        $year = (int) ($this->monthPickerForm['year'] ?? 0);
        $month = (int) ($this->monthPickerForm['month'] ?? 0);

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return;
        }

        $this->visibleMonth = sprintf('%04d-%02d', $year, $month);
        $this->syncMonthPickerForm();
    }

    public function goToToday(): void
    {
        $this->visibleMonth = now()->startOfMonth()->format('Y-m');
        $this->syncMonthPickerForm();
    }

    public function openCalendarItem(string $kind, int $id): void
    {
        if (! in_array($kind, ['checklist', 'payment', 'event'], true)) {
            return;
        }

        $this->selectedCalendarItemKind = $kind;
        $this->selectedCalendarItemId = $id;
    }

    public function closeCalendarItem(): void
    {
        $this->selectedCalendarItemKind = null;
        $this->selectedCalendarItemId = null;
    }

    public function editCalendarEvent(int $eventId): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        /** @var ProjectEvent $event */
        $event = ProjectEvent::query()->findOrFail($eventId);

        $this->editingProjectEventId = $event->id;
        $this->editEventForm = $this->projectEventFormPayload($event);
        $this->closeCalendarItem();
    }

    public function closeProjectEventEditor(): void
    {
        $this->editingProjectEventId = null;
    }

    public function toggleChecklistCompleted(int $itemId, bool $completed): void
    {
        /** @var ProjectChecklistOption $item */
        $item = ProjectChecklistOption::query()->findOrFail($itemId);

        $item->forceFill([
            'completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ])->save();

        $this->timelineItemsCache = null;
    }

    public function saveProjectEventChanges(): void
    {
        if (auth()->user()?->isCustomer()) {
            abort(403);
        }

        if (! $this->editingProjectEventId) {
            return;
        }

        /** @var ProjectEvent $event */
        $event = ProjectEvent::query()->findOrFail($this->editingProjectEventId);
        $payload = $this->projectEventPayloadFromForm($this->editEventForm);

        $event->forceFill($payload)->save();
        $event->refresh();

        $this->timelineItemsCache = null;
        $this->visibleMonth = $event->starts_at->copy()->startOfMonth()->format('Y-m');
        $this->syncMonthPickerForm();
        $this->selectedCalendarItemKind = 'event';
        $this->selectedCalendarItemId = $event->id;
        $this->editingProjectEventId = null;

        Notification::make()
            ->title('Event updated')
            ->success()
            ->send();
    }

    public function getMonthLabel(): string
    {
        return $this->visibleMonthDate()->translatedFormat('F Y');
    }

    public function getMonthPickerOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [$month => Carbon::create()->month($month)->translatedFormat('F')])
            ->all();
    }

    public function getMonthPickerYearOptions(): array
    {
        $years = $this->timelineItems()
            ->flatMap(fn (array $item): array => [$item['start_date']->year, $item['end_date']->year])
            ->unique()
            ->sort()
            ->values();

        if ($years->isEmpty()) {
            return range(now()->year - 2, now()->year + 3);
        }

        return range($years->first() - 1, $years->last() + 1);
    }

    public function getSelectedCalendarItem(): ?array
    {
        if (! $this->selectedCalendarItemKind || ! $this->selectedCalendarItemId) {
            return null;
        }

        return $this->timelineItems()
            ->first(fn (array $item): bool => $item['kind'] === $this->selectedCalendarItemKind && $item['id'] === $this->selectedCalendarItemId);
    }

    public function getCalendarCells(): Collection
    {
        $month = $this->visibleMonthDate();
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $itemsByDate = $this->getMonthOccurrences()->groupBy('date_key');

        $days = collect();
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $days->push([
                'date' => $cursor->copy(),
                'date_key' => $cursor->format('Y-m-d'),
                'is_current_month' => $cursor->month === $month->month,
                'items' => ($itemsByDate->get($cursor->format('Y-m-d')) ?? collect())
                    ->sortBy(fn (array $item): string => sprintf('%s-%s', $item['kind_order'], $item['time_sort']))
                    ->values(),
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    public function getListItems(): Collection
    {
        return $this->timelineItems()
            ->sortBy(fn (array $item): string => sprintf(
                '%s-%s-%s',
                $item['start_date']->format('YmdHis'),
                $item['kind_order'],
                mb_strtolower($item['title']),
            ))
            ->values();
    }

    public function getOverdueSummary(): array
    {
        $today = now()->startOfDay();

        $payments = $this->timelineItems()
            ->where('kind', 'payment')
            ->where('completed', false)
            ->filter(fn (array $item): bool => $item['start_date']->lt($today))
            ->sortBy('start_date')
            ->values();

        $checklists = $this->timelineItems()
            ->where('kind', 'checklist')
            ->where('completed', false)
            ->filter(fn (array $item): bool => $item['start_date']->lt($today))
            ->sortBy('start_date')
            ->values();

        return [
            'payments_count' => $payments->count(),
            'checklists_count' => $checklists->count(),
            'items' => $payments->take(2)->concat($checklists->take(2))->take(4)->values(),
        ];
    }

    public function getDueSoonSummary(): array
    {
        $today = now()->startOfDay();
        $limit = now()->addDays(7)->endOfDay();

        $payments = $this->timelineItems()
            ->where('kind', 'payment')
            ->where('completed', false)
            ->filter(fn (array $item): bool => $item['start_date']->between($today, $limit))
            ->sortBy('start_date')
            ->values();

        $checklists = $this->timelineItems()
            ->where('kind', 'checklist')
            ->where('completed', false)
            ->filter(fn (array $item): bool => $item['start_date']->between($today, $limit))
            ->sortBy('start_date')
            ->values();

        return [
            'payments_count' => $payments->count(),
            'checklists_count' => $checklists->count(),
            'items' => $payments->take(2)->concat($checklists->take(2))->take(4)->values(),
        ];
    }

    public function getNextPaymentDeadlines(): Collection
    {
        $today = now()->startOfDay();

        return $this->timelineItems()
            ->where('kind', 'payment')
            ->where('completed', false)
            ->filter(fn (array $item): bool => $item['start_date']->gte($today))
            ->sortBy('start_date')
            ->take(10)
            ->values();
    }

    public function getNextChecklistDeadlines(): Collection
    {
        $today = now()->startOfDay();

        return $this->timelineItems()
            ->where('kind', 'checklist')
            ->where('completed', false)
            ->filter(fn (array $item): bool => $item['start_date']->gte($today))
            ->sortBy('start_date')
            ->take(10)
            ->values();
    }

    protected function getMonthOccurrences(): Collection
    {
        $monthStart = $this->visibleMonthDate()->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $monthEnd = $this->visibleMonthDate()->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return $this->timelineItems()
            ->flatMap(function (array $item) use ($monthStart, $monthEnd): array {
                $start = $item['start_date']->copy()->startOfDay();
                $end = $item['end_date']->copy()->startOfDay();

                if ($end->lt($monthStart) || $start->gt($monthEnd)) {
                    return [];
                }

                $cursor = $start->copy()->max($monthStart);
                $last = $end->copy()->min($monthEnd);
                $occurrences = [];

                while ($cursor->lte($last)) {
                    $occurrences[] = [
                        ...$item,
                        'date_key' => $cursor->format('Y-m-d'),
                    ];

                    $cursor->addDay();
                }

                return $occurrences;
            })
            ->values();
    }

    protected function timelineItems(): Collection
    {
        if ($this->timelineItemsCache) {
            return $this->timelineItemsCache;
        }

        $projects = Project::query()
            ->whereIn('status', ['proposal', 'confirmed'])
            ->with([
                'projectChecklistOptions',
                'payments.supplier',
                'projectEvents',
            ])
            ->get();

        $checklistItems = $projects->flatMap(function (Project $project): Collection {
            return $project->projectChecklistOptions
                ->where('enabled', true)
                ->filter(fn (ProjectChecklistOption $item): bool => $item->due_date !== null)
                ->map(fn (ProjectChecklistOption $item): array => [
                    'kind' => 'checklist',
                    'kind_order' => '1',
                    'id' => $item->id,
                    'title' => $item->title !== '' ? $item->title : '(Unnamed checklist item)',
                    'subtitle' => $item->details,
                    'project_name' => $project->name,
                    'project_url' => ProjectResource::getUrl('calendar', ['record' => $project]),
                    'project_dashboard_url' => ProjectResource::getUrl('view', ['record' => $project]),
                    'start_date' => $item->due_date->copy()->startOfDay(),
                    'end_date' => $item->due_date->copy()->startOfDay(),
                    'time_sort' => '000000',
                    'color' => 'olive',
                    'completed' => (bool) $item->completed,
                    'payment_status' => null,
                    'is_all_day' => true,
                    'starts_at' => $item->due_date->copy()->startOfDay(),
                    'ends_at' => $item->due_date->copy()->endOfDay(),
                    'program_html' => null,
                ]);
        });

        $paymentItems = $projects->flatMap(function (Project $project): Collection {
            return $project->payments
                ->filter(fn (Payment $payment): bool => $payment->due_date !== null)
                ->map(fn (Payment $payment): array => [
                    'kind' => 'payment',
                    'kind_order' => '2',
                    'id' => $payment->id,
                    'title' => $payment->reason,
                    'subtitle' => 'EUR ' . number_format((float) $payment->amount, 2, ',', '.') . ($payment->supplier?->name ? ' · ' . $payment->supplier->name : ''),
                    'project_name' => $project->name,
                    'project_url' => ProjectResource::getUrl('calendar', ['record' => $project]),
                    'project_dashboard_url' => ProjectResource::getUrl('view', ['record' => $project]),
                    'start_date' => $payment->due_date->copy()->startOfDay(),
                    'end_date' => $payment->due_date->copy()->startOfDay(),
                    'time_sort' => '000000',
                    'color' => 'sky',
                    'completed' => $payment->payment_status === Payment::STATUS_PAID,
                    'payment_status' => $payment->payment_status,
                    'is_all_day' => true,
                    'starts_at' => $payment->due_date->copy()->startOfDay(),
                    'ends_at' => $payment->due_date->copy()->endOfDay(),
                    'program_html' => null,
                ]);
        });

        $eventItems = $projects->flatMap(function (Project $project): Collection {
            return $project->projectEvents
                ->map(fn (ProjectEvent $event): array => [
                    'kind' => 'event',
                    'kind_order' => '3',
                    'id' => $event->id,
                    'title' => $event->title,
                    'subtitle' => $event->description,
                    'project_name' => $project->name,
                    'project_url' => ProjectResource::getUrl('calendar', ['record' => $project]),
                    'project_dashboard_url' => ProjectResource::getUrl('view', ['record' => $project]),
                    'start_date' => $event->starts_at->copy()->startOfDay(),
                    'end_date' => ($event->ends_at ?: $event->starts_at)->copy()->startOfDay(),
                    'time_sort' => $event->is_all_day ? '000000' : $event->starts_at->format('His'),
                    'color' => 'rose',
                    'completed' => false,
                    'payment_status' => null,
                    'is_all_day' => (bool) $event->is_all_day,
                    'starts_at' => $event->starts_at,
                    'ends_at' => $event->ends_at ?: $event->starts_at,
                    'program_html' => $event->program_html,
                ]);
        });

        return $this->timelineItemsCache = $checklistItems
            ->concat($paymentItems)
            ->concat($eventItems)
            ->values();
    }

    protected function visibleMonthDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->visibleMonth)->startOfMonth();
    }

    protected function syncMonthPickerForm(): void
    {
        $date = $this->visibleMonthDate();

        $this->monthPickerForm = [
            'month' => $date->month,
            'year' => (string) $date->year,
        ];
    }

    protected function projectEventFormPayload(ProjectEvent $event): array
    {
        $startsAt = $event->starts_at;
        $endsAt = $event->ends_at ?: $event->starts_at;

        return [
            'title' => $event->title,
            'description' => $event->description ?? '',
            'is_multi_day' => ! $startsAt->isSameDay($endsAt),
            'is_all_day' => (bool) $event->is_all_day,
            'start_date' => $startsAt->format('Y-m-d'),
            'end_date' => $endsAt->format('Y-m-d'),
            'start_time' => $startsAt->format('H:i'),
            'end_time' => $endsAt->format('H:i'),
            'include_program' => filled($event->program_html),
            'program_html' => $event->program_html ?? '',
        ];
    }

    protected function projectEventPayloadFromForm(array $form): array
    {
        if (! (bool) ($form['is_multi_day'] ?? false)) {
            $form['end_date'] = $form['start_date'] ?? null;
        }

        $data = validator($form, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_multi_day' => ['required', 'boolean'],
            'is_all_day' => ['required', 'boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'required_if:is_multi_day,true', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_if:is_all_day,false'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_if:is_all_day,false'],
            'include_program' => ['required', 'boolean'],
            'program_html' => ['nullable', 'string'],
        ])->validate();

        $startDate = Carbon::parse($data['start_date']);
        $endDate = $data['is_multi_day']
            ? Carbon::parse($data['end_date'])
            : Carbon::parse($data['start_date']);

        if ($data['is_all_day']) {
            $startsAt = $startDate->copy()->startOfDay();
            $endsAt = $endDate->copy()->endOfDay();
        } else {
            [$startHour, $startMinute] = array_map('intval', explode(':', (string) $data['start_time']));
            [$endHour, $endMinute] = array_map('intval', explode(':', (string) $data['end_time']));

            $startsAt = $startDate->copy()->setTime($startHour, $startMinute);
            $endsAt = $endDate->copy()->setTime($endHour, $endMinute);

            if ($endsAt->lt($startsAt)) {
                $endsAt = $startsAt->copy();
            }
        }

        return [
            'title' => trim((string) $data['title']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_all_day' => (bool) $data['is_all_day'],
            'program_html' => ($data['include_program'] ?? false) && filled($data['program_html'] ?? null)
                ? trim((string) $data['program_html'])
                : null,
        ];
    }
}
