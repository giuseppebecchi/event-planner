<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\ProjectResource;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use App\Models\ProjectEvent;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static bool $shouldRegisterNavigation = true;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.dashboard';

    public static function canAccess(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard';
    }

    protected function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'hotLeads' => $this->getHotLeads(),
            'projectsInPreparation' => $this->getProjectsInPreparation(),
            'upcomingConfirmedEvents' => $this->getUpcomingConfirmedEvents(),
            'upcomingFollowUps' => $this->getUpcomingFollowUps(),
            'upcomingDeadlines' => $this->getUpcomingDeadlines(),
        ];
    }

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        return [
            [
                'label' => 'Hot leads',
                'value' => Lead::query()
                    ->whereIn('status', ['new', 'under_review', 'call_scheduled', 'proposal_sent'])
                    ->count(),
                'caption' => 'Open opportunities to move forward',
                'tone' => 'olive',
                'icon' => 'heroicon-o-fire',
                'url' => LeadResource::getUrl(),
            ],
            [
                'label' => 'Projects in preparation',
                'value' => Project::query()
                    ->whereIn('status', ['proposal', 'confirmed'])
                    ->where(function ($query) use ($today): void {
                        $query->whereNull('event_date')
                            ->orWhereDate('event_date', '>=', $today);
                    })
                    ->count(),
                'caption' => 'Active weddings being planned',
                'tone' => 'blue',
                'icon' => 'heroicon-o-folder-open',
                'url' => ProjectResource::getUrl(),
            ],
            [
                'label' => 'Confirmed events soon',
                'value' => Project::query()
                    ->where('status', 'confirmed')
                    ->whereDate('event_date', '>=', $today)
                    ->whereDate('event_date', '<=', now()->copy()->addDays(60))
                    ->count(),
                'caption' => 'Events happening in the next 60 days',
                'tone' => 'gold',
                'icon' => 'heroicon-o-calendar-days',
                'url' => ProjectResource::getUrl(),
            ],
            [
                'label' => 'Pending follow ups',
                'value' => LeadFollowUp::query()
                    ->where('status', 'pending')
                    ->count(),
                'caption' => 'Calls, reminders and appointments',
                'tone' => 'rose',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'url' => $this->getPendingFollowUpsUrl(),
            ],
        ];
    }

    protected function getPendingFollowUpsUrl(): string
    {
        $followUp = LeadFollowUp::query()
            ->with('lead')
            ->where('status', 'pending')
            ->orderByRaw("case priority when 'urgent' then 1 when 'high' then 2 when 'normal' then 3 else 4 end")
            ->orderBy('due_at')
            ->first();

        return $followUp?->lead
            ? LeadResource::getUrl('follow-ups', ['record' => $followUp->lead])
            : LeadResource::getUrl();
    }

    protected function getHotLeads(): Collection
    {
        return Lead::query()
            ->withCount([
                'followUps as pending_follow_ups_count' => fn ($query) => $query->where('status', 'pending'),
            ])
            ->whereIn('status', ['new', 'under_review', 'call_scheduled', 'proposal_sent'])
            ->orderByRaw("
                case status
                    when 'call_scheduled' then 1
                    when 'proposal_sent' then 2
                    when 'under_review' then 3
                    else 4
                end
            ")
            ->orderByDesc('requested_at')
            ->limit(6)
            ->get()
            ->map(function (Lead $lead): array {
                return [
                    'name' => $lead->couple_name ?: trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')),
                    'status' => Lead::STATUS_OPTIONS[$lead->status] ?? $lead->status,
                    'region' => $lead->desired_region ?: 'Region to define',
                    'weddingPeriod' => $lead->wedding_period ?: 'Period to define',
                    'guestCount' => $lead->estimated_guest_count ?: null,
                    'pendingFollowUps' => $lead->pending_follow_ups_count,
                    'url' => LeadResource::getUrl('edit', ['record' => $lead]),
                ];
            });
    }

    protected function getProjectsInPreparation(): Collection
    {
        return Project::query()
            ->with('lead')
            ->whereIn('status', ['proposal', 'confirmed'])
            ->orderByRaw("
                case status
                    when 'proposal' then 1
                    when 'confirmed' then 2
                    else 3
                end
            ")
            ->orderBy('event_date')
            ->limit(6)
            ->get()
            ->map(function (Project $project): array {
                return [
                    'name' => $project->name,
                    'couple' => $project->lead?->couple_name ?: $project->coupleNames(),
                    'status' => Project::STATUS_OPTIONS[$project->status] ?? $project->status,
                    'place' => collect([$project->region, $project->locality])->filter()->join(' / ') ?: 'Venue to define',
                    'date' => $project->event_date?->format('d M Y') ?: 'Date to define',
                    'url' => ProjectResource::getUrl('edit', ['record' => $project]),
                ];
            });
    }

    protected function getUpcomingConfirmedEvents(): Collection
    {
        return Project::query()
            ->with('lead')
            ->where('status', 'confirmed')
            ->whereDate('event_date', '>=', now()->startOfDay())
            ->orderBy('event_date')
            ->limit(5)
            ->get()
            ->map(function (Project $project): array {
                return [
                    'name' => $project->name,
                    'couple' => $project->lead?->couple_name ?: $project->coupleNames(),
                    'date' => $project->event_date?->format('d M Y'),
                    'days' => $project->event_date?->diffInDays(now()),
                    'guests' => $project->final_guest_count ?: $project->estimated_guest_count,
                    'place' => collect([$project->region, $project->locality])->filter()->join(' / ') ?: 'Venue to define',
                    'url' => ProjectResource::getUrl('edit', ['record' => $project]),
                ];
            });
    }

    protected function getUpcomingFollowUps(): Collection
    {
        return LeadFollowUp::query()
            ->with('lead')
            ->where('status', 'pending')
            ->orderByRaw("case priority when 'urgent' then 1 when 'high' then 2 when 'normal' then 3 else 4 end")
            ->orderBy('due_at')
            ->limit(8)
            ->get()
            ->map(function (LeadFollowUp $followUp): array {
                return [
                    'subject' => $followUp->subject,
                    'lead' => $followUp->lead?->couple_name ?: 'Lead',
                    'type' => LeadFollowUp::TYPE_OPTIONS[$followUp->follow_up_type] ?? $followUp->follow_up_type,
                    'priority' => LeadFollowUp::PRIORITY_OPTIONS[$followUp->priority] ?? $followUp->priority,
                    'dueAt' => $followUp->due_at?->format('d M Y, H:i') ?: 'No due date',
                    'url' => $followUp->lead ? LeadResource::getUrl('follow-ups', ['record' => $followUp->lead]) : LeadResource::getUrl(),
                ];
            });
    }

    protected function getUpcomingDeadlines(): Collection
    {
        $today = now()->startOfDay();
        $activeProjectStatuses = ['proposal', 'confirmed'];

        $payments = Payment::query()
            ->with(['project', 'supplier', 'categoryBudgetSupplier.categoryBudget'])
            ->whereNotNull('due_date')
            ->where('payment_status', '!=', Payment::STATUS_PAID)
            ->whereHas('project', fn ($query) => $query->whereIn('status', $activeProjectStatuses))
            ->get()
            ->map(function (Payment $payment) use ($today): array {
                $date = $payment->due_date->copy()->startOfDay();
                $proposal = $payment->categoryBudgetSupplier;
                $url = ($payment->project && $proposal?->categoryBudget)
                    ? ProjectResource::getUrl('budget-manage', [
                        'record' => $payment->project,
                        'categoryBudget' => $proposal->categoryBudget,
                    ])
                    : ($payment->project ? ProjectResource::getUrl('suppliers', ['record' => $payment->project]) : ProjectResource::getUrl());

                return $this->deadlinePayload(
                    title: $payment->reason ?: 'Supplier payment',
                    context: collect([$payment->project?->name, $payment->supplier?->name])->filter()->implode(' · '),
                    date: $date,
                    kind: 'Payment',
                    url: $url,
                    today: $today,
                );
            });

        $checklists = ProjectChecklistOption::query()
            ->with(['project', 'supplier'])
            ->where('enabled', true)
            ->where(function ($query): void {
                $query
                    ->where('completed', false)
                    ->orWhereNull('completed');
            })
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->whereHas('project', fn ($query) => $query->whereIn('status', $activeProjectStatuses))
            ->get()
            ->map(fn (ProjectChecklistOption $item): array => $this->deadlinePayload(
                title: $item->title ?: 'Checklist item',
                context: collect([$item->project?->name, $item->supplier?->name])->filter()->implode(' · '),
                date: $item->due_date->copy()->startOfDay(),
                kind: 'Checklist',
                url: $item->project ? ProjectResource::getUrl('checklist', ['record' => $item->project]) : ProjectResource::getUrl(),
                today: $today,
            ));

        $events = ProjectEvent::query()
            ->with('project')
            ->where('starts_at', '>=', $today)
            ->whereHas('project', fn ($query) => $query->whereIn('status', $activeProjectStatuses))
            ->get()
            ->map(fn (ProjectEvent $event): array => $this->deadlinePayload(
                title: $event->title ?: 'Project event',
                context: $event->project?->name ?? 'Project',
                date: $event->starts_at,
                kind: 'Event',
                url: $event->project ? ProjectResource::getUrl('calendar', ['record' => $event->project]) : ProjectResource::getUrl(),
                today: $today,
                includeTime: ! $event->is_all_day,
            ));

        return $payments
            ->concat($checklists)
            ->concat($events)
            ->sortBy(fn (array $deadline): string => sprintf('%s-%s-%s', $deadline['date_sort'], $deadline['kind'], mb_strtolower($deadline['title'])))
            ->take(10)
            ->values();
    }

    protected function deadlinePayload(
        string $title,
        string $context,
        Carbon $date,
        string $kind,
        string $url,
        Carbon $today,
        bool $includeTime = false,
    ): array {
        $dateDay = $date->copy()->startOfDay();
        $days = $today->diffInDays($dateDay, false);
        $isOverdue = $days < 0;

        $tone = match (true) {
            $isOverdue || $days === 0 => 'rose',
            $days <= 7 => 'gold',
            $days <= 30 => 'blue',
            default => 'olive',
        };

        $urgency = match (true) {
            $isOverdue => abs($days) . ' days overdue',
            $days === 0 => 'Due today',
            $days === 1 => 'Due tomorrow',
            $days <= 30 => $days . ' days left',
            default => $date->format($includeTime ? 'd M Y, H:i' : 'd M Y'),
        };

        return [
            'title' => $title,
            'context' => $context ?: 'Project',
            'due' => $date->format($includeTime ? 'd M Y, H:i' : 'd M Y'),
            'urgency' => $urgency,
            'kind' => $kind,
            'tone' => $tone,
            'url' => $url,
            'date_sort' => $date->format('YmdHis'),
            'is_critical' => $days <= 7,
        ];
    }
}
