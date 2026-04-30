<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Payment;
use App\Models\ProjectEvent;
use App\Models\ProjectChecklistOption;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ViewProjectCalendar extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-calendar';

    protected static ?string $breadcrumb = 'Calendar';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $calendarView = 'month';

    public string $visibleMonth = '';
    public ?string $selectedCalendarItemKind = null;
    public ?int $selectedCalendarItemId = null;

    public array $monthPickerForm = [
        'month' => '',
        'year' => '',
    ];

    public array $eventForm = [
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

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $anchorDate = $this->getRecord()->event_start_date ?: now();
        $this->visibleMonth = $anchorDate->copy()->startOfMonth()->format('Y-m');
        $this->syncMonthPickerForm();

        $this->eventForm['start_date'] = $anchorDate->format('Y-m-d');
        $this->eventForm['end_date'] = $anchorDate->format('Y-m-d');
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

    public function goToEventDate(): void
    {
        $eventDate = $this->getRecord()->event_start_date;

        if (! $eventDate) {
            return;
        }

        $this->visibleMonth = $eventDate->copy()->startOfMonth()->format('Y-m');
        $this->syncMonthPickerForm();
    }

    public function selectCalendarDay(string $date): void
    {
        $this->eventForm['start_date'] = $date;

        if (! ($this->eventForm['is_multi_day'] ?? false)) {
            $this->eventForm['end_date'] = $date;
        }
    }

    public function toggleChecklistCompleted(int $itemId, bool $completed): void
    {
        /** @var ProjectChecklistOption $item */
        $item = $this->getRecord()->projectChecklistOptions()->findOrFail($itemId);

        $item->forceFill([
            'completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ])->save();

        $this->getRecord()->unsetRelation('projectChecklistOptions');
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

    public function saveProjectEvent(): void
    {
        $data = validator($this->eventForm, [
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

        $this->getRecord()->projectEvents()->create([
            'title' => trim((string) $data['title']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_all_day' => (bool) $data['is_all_day'],
            'program_html' => ($data['include_program'] ?? false) && filled($data['program_html'] ?? null)
                ? trim((string) $data['program_html'])
                : null,
        ]);

        $this->getRecord()->unsetRelation('projectEvents');

        $this->eventForm = [
            'title' => '',
            'description' => '',
            'is_multi_day' => false,
            'is_all_day' => true,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $startDate->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'include_program' => false,
            'program_html' => '',
        ];

        Notification::make()
            ->title('Event created')
            ->success()
            ->send();
    }

    public function getCalendarSummary(): array
    {
        $items = $this->getTimelineItems();

        return [
            'checklist' => $items->where('kind', 'checklist')->count(),
            'payments' => $items->where('kind', 'payment')->count(),
            'events' => $items->where('kind', 'event')->count(),
            'total' => $items->count(),
        ];
    }

    public function getSelectedCalendarItem(): ?array
    {
        if (! $this->selectedCalendarItemKind || ! $this->selectedCalendarItemId) {
            return null;
        }

        return $this->getTimelineItems()
            ->first(fn (array $item): bool => $item['kind'] === $this->selectedCalendarItemKind && $item['id'] === $this->selectedCalendarItemId);
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
        $eventYear = $this->getRecord()->event_start_date?->year ?? now()->year;
        $startYear = min(now()->year - 2, $eventYear - 2);
        $endYear = max(now()->year + 3, $eventYear + 3);

        return range($startYear, $endYear);
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
                    ->sortBy(fn (array $item): string => sprintf(
                        '%s-%s',
                        $item['kind_order'],
                        $item['time_sort'],
                    ))
                    ->values(),
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    public function getListItems(): Collection
    {
        return $this->getTimelineItems()
            ->sortBy(fn (array $item): string => sprintf(
                '%s-%s-%s',
                $item['start_date']->format('YmdHis'),
                $item['kind_order'],
                mb_strtolower($item['title']),
            ))
            ->values();
    }

    protected function getMonthOccurrences(): Collection
    {
        $monthStart = $this->visibleMonthDate()->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $monthEnd = $this->visibleMonthDate()->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return $this->getTimelineItems()
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

    protected function getTimelineItems(): Collection
    {
        $project = $this->getRecord()->loadMissing([
            'projectChecklistOptions',
            'payments.supplier',
            'projectEvents',
        ]);

        $limitDate = $project->event_end_date ?: $project->event_start_date;

        $checklistItems = $project->projectChecklistOptions
            ->where('enabled', true)
            ->filter(fn (ProjectChecklistOption $item): bool => $item->due_date !== null)
            ->filter(fn (ProjectChecklistOption $item): bool => ! $limitDate || $item->due_date->lte($limitDate))
            ->map(fn (ProjectChecklistOption $item): array => [
                'kind' => 'checklist',
                'kind_order' => '1',
                'id' => $item->id,
                'title' => $item->title !== '' ? $item->title : '(Unnamed checklist item)',
                'subtitle' => $item->details,
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

        $paymentItems = $project->payments
            ->filter(fn (Payment $payment): bool => $payment->due_date !== null)
            ->filter(fn (Payment $payment): bool => ! $limitDate || $payment->due_date->lte($limitDate))
            ->map(fn (Payment $payment): array => [
                'kind' => 'payment',
                'kind_order' => '2',
                'id' => $payment->id,
                'title' => $payment->reason,
                'subtitle' => 'EUR ' . number_format((float) $payment->amount, 2, ',', '.') . ($payment->supplier?->name ? ' · ' . $payment->supplier->name : ''),
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

        $eventItems = $project->projectEvents
            ->filter(fn (ProjectEvent $event): bool => ! $limitDate || $event->starts_at->lte($limitDate->copy()->endOfDay()))
            ->map(fn (ProjectEvent $event): array => [
                'kind' => 'event',
                'kind_order' => '3',
                'id' => $event->id,
                'title' => $event->title,
                'subtitle' => $event->description,
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

        return $checklistItems
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
}
