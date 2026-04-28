@php
    use App\Filament\Resources\ProjectResource;

    $daysToGo = $record->event_start_date
        ? now()->startOfDay()->diffInDays($record->event_start_date->startOfDay(), false)
        : null;

    $dateLabel = $record->event_start_date?->format('F j, Y') ?? 'Date to be defined';
    $locationLabel = collect([$record->locality, $record->region])->filter()->implode(', ') ?: 'Location to be defined';
    $partnerLabel = collect([$record->partner_one_name, $record->partner_two_name])->filter()->implode(' & ') ?: 'Partners not set';
    $overviewUrl = ProjectResource::getUrl('view', ['record' => $record]);
    $budgetUrl = ProjectResource::getUrl('budget', ['record' => $record]);
    $infoUrl = ProjectResource::getUrl('edit', ['record' => $record]);
@endphp

<section class="wm-event-card wm-event-top">
    <div class="wm-event-top-head">
        <div class="wm-event-top-primary">
            <h2 class="wm-event-top-title">{{ $record->name }}</h2>
            <div class="wm-event-top-meta">
                <span>{{ $locationLabel }}</span>
                <span>{{ $dateLabel }}</span>
                <span>{{ \App\Models\Project::STATUS_OPTIONS[$record->status] ?? $record->status }}</span>
                <span>{{ $partnerLabel }}</span>
            </div>
        </div>

        <div class="wm-event-top-side">
            <div class="wm-event-summary-chip">
                <div>
                    <p class="wm-event-summary-chip-label">Guests</p>
                    <p class="wm-event-summary-chip-value">{{ $record->final_guest_count ?: $record->estimated_guest_count ?: '—' }}</p>
                </div>
            </div>

            <div class="wm-event-countdown">
                <div class="wm-event-countdown-head">
                    <p class="wm-event-countdown-label">Countdown</p>

                    <button
                        type="button"
                        class="wm-event-countdown-edit"
                        wire:click="openProjectDateEditor"
                        aria-label="{{ $record->event_start_date ? 'Edit event date' : 'Set event date' }}"
                    >
                        <x-heroicon-o-pencil-square />
                    </button>
                </div>
                <p class="wm-event-countdown-value">{{ $daysToGo !== null ? $daysToGo . ' days to go' : 'Date pending' }}</p>
                <p class="wm-event-countdown-meta">{{ $dateLabel }}</p>
            </div>
        </div>
    </div>

    <div class="wm-event-top-date-tools">
        @if ($showProjectDateEditor)
            <div class="wm-event-date-editor">
                <label class="wm-event-date-toggle">
                    <input type="checkbox" wire:model.live="projectDateForm.is_multi_day">
                    <span>Event spans multiple days</span>
                </label>

                @if (! ($projectDateForm['is_multi_day'] ?? false))
                    <div class="wm-event-date-grid is-single">
                        <div>
                            <label class="wm-event-date-label" for="project-single-date">Event date</label>
                            <input
                                id="project-single-date"
                                type="date"
                                class="wm-event-date-input"
                                wire:model="projectDateForm.single_date"
                            >
                        </div>
                    </div>
                @else
                    <div class="wm-event-date-grid">
                        <div>
                            <label class="wm-event-date-label" for="project-start-date">Start date</label>
                            <input
                                id="project-start-date"
                                type="date"
                                class="wm-event-date-input"
                                wire:model="projectDateForm.start_date"
                            >
                        </div>

                        <div>
                            <label class="wm-event-date-label" for="project-end-date">End date</label>
                            <input
                                id="project-end-date"
                                type="date"
                                class="wm-event-date-input"
                                wire:model="projectDateForm.end_date"
                            >
                        </div>
                    </div>
                @endif

                <div class="wm-event-date-actions">
                    <x-filament::button size="sm" wire:click="saveProjectDateEditor">
                        Save
                    </x-filament::button>

                    <x-filament::button color="gray" size="sm" wire:click="cancelProjectDateEditor">
                        Cancel
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>

    <nav class="wm-event-workspace" aria-label="Event workspace navigation">
        <a href="{{ $infoUrl }}" class="wm-event-workspace-link {{ ($activeSection ?? null) === 'info' ? 'is-active' : '' }}">Info</a>
        <a href="{{ $budgetUrl }}" class="wm-event-workspace-link {{ ($activeSection ?? null) === 'budget' ? 'is-active' : '' }}">Budget</a>
        <a href="{{ $overviewUrl }}#checklist" class="wm-event-workspace-link">Checklist</a>
        <a href="{{ $overviewUrl }}#calendar" class="wm-event-workspace-link">Calendar</a>
        <a href="{{ $overviewUrl }}#timeline" class="wm-event-workspace-link">Timeline</a>
        <a href="{{ $overviewUrl }}#design-studio" class="wm-event-workspace-link">Design Studio</a>
        <a href="{{ $overviewUrl }}#guests" class="wm-event-workspace-link">Guests</a>
        <a href="{{ $overviewUrl }}#layout-seating" class="wm-event-workspace-link">Layout &amp; Seating</a>
        <a href="{{ $overviewUrl }}#contacts" class="wm-event-workspace-link">Contacts</a>
        <a href="{{ $overviewUrl }}#notes" class="wm-event-workspace-link">Notes</a>
    </nav>
</section>
