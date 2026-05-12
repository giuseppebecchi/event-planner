<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $groups = $this->getGuestGroups();
        $fields = $this->getFields();
        $summary = $this->getSummary();
        $responseSummaries = $this->getResponseSummaries();
    @endphp

    <style>
        .wm-rsvp-page { display: flex; flex-direction: column; gap: 1rem; }
        .wm-rsvp-shell { width: min(1440px, calc(100% - 2rem)); margin: 0 auto; display: grid; gap: 1rem; }
        .wm-event-card { border: 1px solid var(--cup-border-soft, #e8e3dc); border-radius: 1.35rem; background: rgba(255,255,255,.92); box-shadow: 0 20px 42px rgba(45,42,38,.06); }
        .wm-event-top { display: flex; flex-direction: column; gap: .85rem; align-items: start; padding: .9rem 1rem 1rem; }
        .wm-event-top-head { width: 100%; display: grid; grid-template-columns: minmax(0,1fr) auto; gap: .9rem 1rem; align-items: center; }
        .wm-event-top-title { margin: 0; font-family: 'Cinzel', serif; font-size: clamp(1.2rem,1.8vw,1.65rem); line-height: 1.08; color: #2d2a26; }
        .wm-event-top-meta { display: flex; flex-wrap: wrap; gap: .6rem .95rem; margin-top: .4rem; color: #746d66; font-size: .86rem; line-height: 1.5; }
        .wm-event-top-meta span:not(:last-child)::after { content: "\2022"; margin-left: .95rem; color: #c9a96a; }
        .wm-event-top-side { display: flex; align-items: center; gap: .55rem; }
        .wm-event-summary-chip { display: inline-flex; align-items: center; justify-content: center; min-width: 6rem; padding: .62rem .78rem; border-radius: 1rem; border: 1px solid rgba(201,169,106,.22); background: rgba(255,255,255,.85); color: #5f5953; }
        .wm-event-summary-chip-label { margin: 0; font-size: .62rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #9a8f82; }
        .wm-event-summary-chip-value { margin: .16rem 0 0; font-size: .98rem; font-weight: 700; color: #2d2a26; }
        .wm-event-countdown { min-width: 11.5rem; padding: .62rem .82rem; border-radius: 1rem; background: linear-gradient(160deg, rgba(46,74,98,.96), rgba(36,60,81,.98)); color: #f7f3ed; }
        .wm-event-countdown-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .wm-event-countdown-label { margin: 0; font-size: .66rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.64); }
        .wm-event-countdown-edit { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border: 0; border-radius: 999px; background: rgba(255,255,255,.1); color: rgba(255,255,255,.86); cursor: pointer; }
        .wm-event-countdown-edit svg { width: 1rem; height: 1rem; }
        .wm-event-countdown-value { margin: .18rem 0 0; color: #fff; font-size: 1.08rem; font-weight: 700; }
        .wm-event-countdown-meta { margin: .1rem 0 0; color: rgba(255,255,255,.72); font-size: .8rem; }
        .wm-event-workspace { display: flex; align-items: center; gap: .4rem; overflow-x: auto; width: 100%; padding: .28rem; border-radius: 1.2rem; background: rgba(247,243,237,.96); scrollbar-width: none; border: 1px solid #ece5dd; }
        .wm-event-workspace::-webkit-scrollbar { display: none; }
        .wm-event-workspace-link { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; min-height: 2.45rem; padding: 0 .88rem; border-radius: 999px; color: #746d66; font-size: .69rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; white-space: nowrap; text-decoration: none; }
        .wm-event-workspace-link.is-active { background: rgba(122,143,123,.14); color: #2d7a39; }
        .wm-event-top-date-tools { width: 100%; }
        .wm-event-date-editor { display: grid; gap: .85rem; width: 100%; max-width: 38rem; padding: 1rem; border-radius: 1rem; background: #fbf8f4; border: 1px solid #ece5dd; }
        .wm-event-date-toggle { display: inline-flex; align-items: center; gap: .6rem; color: #4d473f; font-weight: 600; }
        .wm-event-date-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: .75rem; }
        .wm-event-date-grid.is-single { grid-template-columns: minmax(0,1fr); max-width: 16rem; }
        .wm-event-date-label { display: block; margin-bottom: .35rem; color: #5e5852; font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .wm-event-date-input { width: 100%; min-height: 2.9rem; border-radius: .95rem; border: 1px solid #ddd2c5; background: #fff; padding: 0 .95rem; color: #2d2a26; }
        .wm-event-date-actions { display: flex; flex-wrap: wrap; gap: .7rem; }
        .wm-rsvp-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem; }
        .wm-rsvp-stats, .wm-rsvp-actions { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; }
        .wm-rsvp-stat { min-width: 13rem; padding: .85rem 1rem; border: 1px solid rgba(201,169,106,.22); border-radius: .9rem; background: #fffdf9; }
        .wm-rsvp-stat-label { margin: 0; color: #9a8f82; font-size: .65rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        .wm-rsvp-stat-value { margin: .18rem 0 0; color: #2d2a26; font-size: 1.35rem; font-weight: 900; }
        .wm-rsvp-button { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 2.8rem; padding: 0 1rem; border: 1px solid #b9975b; border-radius: .45rem; background: #b9975b; color: #fff; font-size: .74rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; text-decoration: none; cursor: pointer; }
        .wm-rsvp-button.is-secondary { background: #fffdfa; color: #7a5e28; border-color: #dfd0bf; }
        .wm-rsvp-button svg { width: 1rem; height: 1rem; }
        .wm-rsvp-table-card { overflow: hidden; }
        .wm-rsvp-table-scroll { overflow-x: auto; }
        .wm-rsvp-table { width: 100%; min-width: 1280px; border-collapse: collapse; font-size: .86rem; }
        .wm-rsvp-table th { padding: .85rem .75rem; background: #f7f4ef; color: #4f4943; text-align: left; font-size: .72rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; }
        .wm-rsvp-table td { padding: .78rem .75rem; border-top: 1px solid #eee7de; color: #514b45; vertical-align: top; }
        .wm-rsvp-table tbody tr:hover { background: #fffaf2; }
        .wm-rsvp-group-row td { background: #fbf7f0; color: #4f4943; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .wm-rsvp-name { color: #2d2a26; font-weight: 800; }
        .wm-rsvp-chip { display: inline-flex; align-items: center; min-height: 1.65rem; padding: 0 .6rem; border-radius: 999px; background: #f4eee6; color: #61584d; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
        .wm-rsvp-chip.is-complete { background: rgba(68,101,69,.1); color: #2f7a3a; }
        .wm-rsvp-muted { color: #968c82; }
        .wm-rsvp-response { max-width: 22rem; white-space: normal; line-height: 1.55; }
        .wm-rsvp-summary { padding: 1rem; display: grid; gap: 1rem; }
        .wm-rsvp-summary-title { margin: 0; color: #2d2a26; font-size: 1rem; font-weight: 900; }
        .wm-rsvp-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: .8rem; }
        .wm-rsvp-summary-card { border: 1px solid #eee2d2; border-radius: .9rem; background: #fffdf9; padding: .9rem; }
        .wm-rsvp-summary-card h3 { margin: 0 0 .6rem; color: #3c352e; font-size: .82rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .wm-rsvp-summary-item { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .35rem 0; border-top: 1px solid #f1e8dd; color: #5f574f; }
        .wm-rsvp-summary-item:first-of-type { border-top: 0; }
        .wm-rsvp-summary-count { color: #2d2a26; font-weight: 900; }
        @media (max-width: 1000px) {
            .wm-rsvp-shell { width: min(100%, calc(100% - 1rem)); }
            .wm-event-top-head { grid-template-columns: minmax(0,1fr); }
            .wm-event-top-side { flex-direction: column; align-items: stretch; }
            .wm-event-summary-chip, .wm-event-countdown { width: 100%; }
            .wm-rsvp-toolbar { align-items: stretch; flex-direction: column; }
        }
    </style>

    <div class="wm-rsvp-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'guests',
        ])

        <div class="wm-rsvp-shell">
            <section class="wm-event-card wm-rsvp-toolbar">
                <div class="wm-rsvp-stats">
                    <article class="wm-rsvp-stat">
                        <p class="wm-rsvp-stat-label">Response status</p>
                        <p class="wm-rsvp-stat-value">{{ $summary['completed'] }} / {{ $summary['total_parties'] }}</p>
                    </article>
                    <article class="wm-rsvp-stat">
                        <p class="wm-rsvp-stat-label">Confirmed guests</p>
                        <p class="wm-rsvp-stat-value">{{ $summary['confirmed_people'] }}</p>
                    </article>
                </div>

                <div class="wm-rsvp-actions">
                    <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('guests', ['record' => $record]) }}" class="wm-rsvp-button is-secondary">
                        <x-heroicon-o-arrow-left />
                        <span>Back to guests</span>
                    </a>
                    <button type="button" class="wm-rsvp-button is-secondary" wire:click="exportRsvpResponses">
                        <x-heroicon-o-document-arrow-down />
                        <span>Download XLSX</span>
                    </button>
                    <button type="button" class="wm-rsvp-button" wire:click="downloadGuestsPdf">
                        <x-heroicon-o-document-text />
                        <span>Guests PDF</span>
                    </button>
                </div>
            </section>

            <section class="wm-event-card wm-rsvp-table-card">
                <div class="wm-rsvp-table-scroll">
                    <table class="wm-rsvp-table">
                        <thead>
                            <tr>
                                <th>RSVP #</th>
                                <th>Guest</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Completed at</th>
                                <th>Confirmed</th>
                                <th>Ceremony</th>
                                <th>Reception</th>
                                @foreach ($fields as $field)
                                    <th>{{ $field['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groups as $group)
                                @php($guest = $group['guest'])
                                <tr class="wm-rsvp-group-row">
                                    <td colspan="{{ 8 + count($fields) }}">
                                        RSVP {{ $group['rsvp_number'] ?: '-' }} - {{ $group['label'] }}
                                        <span class="wm-rsvp-muted">({{ count($group['rows']) }} guests)</span>
                                    </td>
                                </tr>
                                @foreach ($group['rows'] as $row)
                                    <tr>
                                        <td>{{ $guest->rsvp_number ?: '-' }}</td>
                                        <td>
                                            <div class="wm-rsvp-name">{{ $row['name'] }}</div>
                                            <div class="wm-rsvp-muted">{{ $guest->email ?: $guest->phone ?: 'No contact' }}</div>
                                        </td>
                                        <td>{{ $row['role'] }}</td>
                                        <td>
                                            @if ($guest->rsvp_completed_at)
                                                <span class="wm-rsvp-chip is-complete">Completed</span>
                                            @else
                                                <span class="wm-rsvp-chip">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $guest->rsvp_completed_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                        <td>{{ $this->formatAttendanceForRow($row) ?: '-' }}</td>
                                        <td>{{ (int) $guest->ceremony === 1 ? 'Yes' : ((int) $guest->ceremony === -1 ? 'No' : '-') }}</td>
                                        <td>{{ (int) $guest->reception === 1 ? 'Yes' : ((int) $guest->reception === -1 ? 'No' : '-') }}</td>
                                        @foreach ($fields as $field)
                                            <td class="wm-rsvp-response">{{ $this->formatFieldResponseForRow($row, $field) ?: '-' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="{{ 8 + count($fields) }}" class="wm-rsvp-muted">No guests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($responseSummaries !== [])
                <section class="wm-event-card wm-rsvp-summary">
                    <h2 class="wm-rsvp-summary-title">Response summary</h2>
                    <div class="wm-rsvp-summary-grid">
                        @foreach ($responseSummaries as $responseSummary)
                            <article class="wm-rsvp-summary-card">
                                <h3>{{ $responseSummary['label'] }}</h3>
                                @forelse ($responseSummary['items'] as $item)
                                    <div class="wm-rsvp-summary-item">
                                        <span>{{ $item['label'] }}</span>
                                        <span class="wm-rsvp-summary-count">{{ $item['count'] }}</span>
                                    </div>
                                @empty
                                    <div class="wm-rsvp-muted">No responses yet.</div>
                                @endforelse
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
