<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\ProjectResource;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\Project;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static bool $shouldRegisterNavigation = true;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.dashboard';

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
            'fakeDeadlines' => $this->getFakeDeadlines(),
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
            ],
            [
                'label' => 'Projects in preparation',
                'value' => Project::query()
                    ->whereIn('status', ['proposal', 'confirmed'])
                    ->where(function ($query) use ($today): void {
                        $query->whereNull('event_start_date')
                            ->orWhereDate('event_start_date', '>=', $today);
                    })
                    ->count(),
                'caption' => 'Active weddings being planned',
                'tone' => 'blue',
                'icon' => 'heroicon-o-folder-open',
            ],
            [
                'label' => 'Confirmed events soon',
                'value' => Project::query()
                    ->where('status', 'confirmed')
                    ->whereDate('event_start_date', '>=', $today)
                    ->whereDate('event_start_date', '<=', now()->copy()->addDays(60))
                    ->count(),
                'caption' => 'Events happening in the next 60 days',
                'tone' => 'gold',
                'icon' => 'heroicon-o-calendar-days',
            ],
            [
                'label' => 'Pending follow ups',
                'value' => LeadFollowUp::query()
                    ->where('status', 'pending')
                    ->count(),
                'caption' => 'Calls, reminders and appointments',
                'tone' => 'rose',
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
        ];
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
            ->orderBy('event_start_date')
            ->limit(6)
            ->get()
            ->map(function (Project $project): array {
                return [
                    'name' => $project->name,
                    'couple' => $project->lead?->couple_name ?: trim(($project->partner_one_name ?? '') . ' ' . ($project->partner_two_name ?? '')),
                    'status' => Project::STATUS_OPTIONS[$project->status] ?? $project->status,
                    'place' => collect([$project->region, $project->locality])->filter()->join(' / ') ?: 'Location to define',
                    'date' => $project->event_start_date?->format('d M Y') ?: 'Date to define',
                    'url' => ProjectResource::getUrl('edit', ['record' => $project]),
                ];
            });
    }

    protected function getUpcomingConfirmedEvents(): Collection
    {
        return Project::query()
            ->with('lead')
            ->where('status', 'confirmed')
            ->whereDate('event_start_date', '>=', now()->startOfDay())
            ->orderBy('event_start_date')
            ->limit(5)
            ->get()
            ->map(function (Project $project): array {
                return [
                    'name' => $project->name,
                    'couple' => $project->lead?->couple_name ?: trim(($project->partner_one_name ?? '') . ' ' . ($project->partner_two_name ?? '')),
                    'date' => $project->event_start_date?->format('d M Y'),
                    'days' => $project->event_start_date?->diffInDays(now()),
                    'guests' => $project->final_guest_count ?: $project->estimated_guest_count,
                    'place' => collect([$project->region, $project->locality])->filter()->join(' / ') ?: 'Location to define',
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

    protected function getFakeDeadlines(): array
    {
        $base = now()->startOfDay();

        return [
            [
                'title' => 'Venue second deposit',
                'context' => 'Alessia & Tommaso',
                'due' => $base->copy()->addDays(2)->format('d M Y'),
                'kind' => 'Payment',
                'tone' => 'gold',
            ],
            [
                'title' => 'Reminder to share final moodboard',
                'context' => 'Emily & James',
                'due' => $base->copy()->addDays(4)->format('d M Y'),
                'kind' => 'Reminder',
                'tone' => 'olive',
            ],
            [
                'title' => 'Photographer balance due',
                'context' => 'Martina & Luca',
                'due' => $base->copy()->addDays(6)->format('d M Y'),
                'kind' => 'Payment',
                'tone' => 'rose',
            ],
            [
                'title' => 'Schedule tasting confirmation',
                'context' => 'Sophie & Daniel',
                'due' => $base->copy()->addDays(7)->format('d M Y'),
                'kind' => 'Planning',
                'tone' => 'blue',
            ],
        ];
    }
}
