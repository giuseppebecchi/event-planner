<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use App\Models\ProjectEvent;
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

    public array $monthPickerForm = [
        'month' => '',
        'year' => '',
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
}
