<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $days = $this->getTimelineDays();
        $recapChecklistItems = $this->getRecapChecklistItems();
    @endphp

    <style>
        .wm-recap-page { display: flex; flex-direction: column; gap: 1rem; }
        .wm-event-card,
        .wm-recap-card {
            border: 1px solid var(--cup-border-soft, #e8e3dc);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 20px 42px rgba(45, 42, 38, 0.06);
        }
        .wm-event-top {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            align-items: start;
            padding: 0.9rem 1rem 1rem;
        }
        .wm-event-top-head {
            width: 100%;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.9rem 1rem;
            align-items: center;
        }
        .wm-event-top-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.2rem, 1.8vw, 1.65rem);
            line-height: 1.08;
            color: #2d2a26;
        }
        .wm-event-top-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem 0.95rem;
            margin-top: 0.4rem;
            color: #746d66;
            font-size: 0.86rem;
            line-height: 1.5;
        }
        .wm-event-top-meta span { position: relative; }
        .wm-event-top-meta span:not(:last-child)::after {
            content: "•";
            margin-left: 0.95rem;
            color: #c9a96a;
        }
        .wm-event-top-side {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }
        .wm-event-summary-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 6rem;
            padding: 0.62rem 0.78rem;
            border-radius: 1rem;
            border: 1px solid rgba(201, 169, 106, 0.22);
            background: rgba(255, 255, 255, 0.85);
            color: #5f5953;
        }
        .wm-event-summary-chip-label {
            margin: 0;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #9a8f82;
        }
        .wm-event-summary-chip-value {
            margin: 0.16rem 0 0;
            font-size: 0.98rem;
            font-weight: 700;
            color: #2d2a26;
        }
        .wm-event-countdown {
            min-width: 11.5rem;
            padding: 0.62rem 0.82rem;
            border-radius: 1rem;
            background: linear-gradient(160deg, rgba(46, 74, 98, 0.96), rgba(36, 60, 81, 0.98));
            color: #f7f3ed;
        }
        .wm-event-countdown-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .wm-event-countdown-label {
            margin: 0;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.64);
        }
        .wm-event-countdown-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.10);
            color: rgba(255, 255, 255, 0.86);
            cursor: pointer;
        }
        .wm-event-countdown-edit svg { width: 1rem; height: 1rem; }
        .wm-event-countdown-value {
            margin: 0.18rem 0 0;
            color: #fff;
            font-size: 1.08rem;
            font-weight: 700;
        }
        .wm-event-countdown-meta {
            margin: 0.1rem 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.8rem;
        }
        .wm-event-workspace {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            overflow-x: auto;
            width: 100%;
            padding: 0.28rem;
            border-radius: 1.2rem;
            background: rgba(247, 243, 237, 0.96);
            scrollbar-width: none;
            border: 1px solid #ece5dd;
        }
        .wm-event-workspace::-webkit-scrollbar { display: none; }
        .wm-event-workspace-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            min-height: 2.45rem;
            padding: 0 0.88rem;
            border-radius: 999px;
            color: #746d66;
            font-size: 0.69rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            white-space: nowrap;
            text-decoration: none;
        }
        .wm-event-workspace-link.is-active {
            background: rgba(122, 143, 123, 0.14);
            color: #2d7a39;
        }
        .wm-event-top-date-tools { width: 100%; }
        .wm-event-date-editor {
            display: grid;
            gap: 0.85rem;
            width: 100%;
            max-width: 38rem;
            padding: 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }
        .wm-event-date-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: #4d473f;
            font-weight: 600;
        }
        .wm-event-date-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }
        .wm-event-date-grid.is-single {
            grid-template-columns: minmax(0, 1fr);
            max-width: 16rem;
        }
        .wm-event-date-label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .wm-event-date-input {
            width: 100%;
            min-height: 2.9rem;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0 0.95rem;
            color: #2d2a26;
        }
        .wm-event-date-actions { display: flex; flex-wrap: wrap; gap: 0.7rem; }
        .wm-recap-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.15rem 1.25rem;
        }
        .wm-recap-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .wm-recap-title {
            margin: 0.28rem 0 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
        }
        .wm-recap-copy {
            margin: 0.35rem 0 0;
            color: #746d66;
            font-size: 0.86rem;
            line-height: 1.5;
        }
        .wm-recap-export {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 2.45rem;
            border: 0;
            border-radius: 999px;
            padding: 0 0.95rem;
            background: #2e4a62;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }
        .wm-recap-export svg { width: 1rem; height: 1rem; }
        .wm-recap-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(20rem, 0.85fr);
            gap: 1rem;
            align-items: start;
        }
        .wm-recap-section { padding: 1rem 1.15rem; }
        .wm-recap-day {
            display: grid;
            gap: 0.6rem;
            padding: 0.9rem 0;
            border-top: 1px solid #ece5dd;
        }
        .wm-recap-day:first-child { border-top: 0; padding-top: 0; }
        .wm-recap-day-title {
            margin: 0;
            color: #2d2a26;
            font-size: 1rem;
            font-weight: 900;
        }
        .wm-recap-row {
            display: grid;
            grid-template-columns: 4.8rem minmax(0, 1fr);
            gap: 0.75rem;
            padding: 0.48rem 0;
            border-top: 1px solid #f0e8df;
        }
        .wm-recap-row:first-of-type { border-top: 0; }
        .wm-recap-time {
            color: #2e4a62;
            font-size: 0.84rem;
            font-weight: 900;
            white-space: nowrap;
        }
        .wm-recap-item-title {
            margin: 0;
            color: #2d2a26;
            font-size: 0.9rem;
            font-weight: 800;
        }
        .wm-recap-meta,
        .wm-recap-text {
            margin: 0.18rem 0 0;
            color: #746d66;
            font-size: 0.78rem;
            line-height: 1.45;
        }
        .wm-recap-checklist-list { display: grid; gap: 0.85rem; }
        .wm-recap-checklist-item {
            padding: 0.85rem 0;
            border-top: 1px solid #ece5dd;
        }
        .wm-recap-checklist-item:first-child { border-top: 0; padding-top: 0; }
        .wm-recap-html {
            margin-top: 0.35rem;
            color: #4d473f;
            font-size: 0.84rem;
            line-height: 1.55;
        }
        .wm-recap-empty {
            padding: 1rem 0;
            color: #8b847d;
            font-size: 0.88rem;
        }
        @media (max-width: 1100px) {
            .wm-event-top-head,
            .wm-event-date-grid,
            .wm-recap-head,
            .wm-recap-layout { grid-template-columns: 1fr; }
            .wm-event-top-side,
            .wm-recap-head {
                flex-direction: column;
                align-items: stretch;
            }
            .wm-event-summary-chip,
            .wm-event-countdown { width: 100%; }
        }
    </style>

    <div class="wm-recap-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'recap',
        ])

        <section class="wm-recap-card wm-recap-head">
            <div>
                <p class="wm-recap-label">Recap</p>
                <h2 class="wm-recap-title">Preview</h2>
                <p class="wm-recap-copy">Operational preview of the timeline PDF plus checklist texts marked for recap.</p>
            </div>
            <button type="button" class="wm-recap-export" wire:click="exportTimelinePdf">
                <x-heroicon-o-document-arrow-down />
                <span>Esporta PDF</span>
            </button>
        </section>

        <section class="wm-recap-layout">
            <article class="wm-recap-card wm-recap-section">
                <p class="wm-recap-label">Timeline</p>
                @forelse ($days as $day)
                    <section class="wm-recap-day">
                        <h3 class="wm-recap-day-title">{{ $day['date']->format('l, F j, Y') }}</h3>
                        @forelse ($day['items'] as $item)
                            <div class="wm-recap-row">
                                <div class="wm-recap-time">{{ $item->start_time?->format('H:i') ?? '-' }}</div>
                                <div>
                                    <p class="wm-recap-item-title">{{ $item->title }}</p>
                                    <p class="wm-recap-meta">
                                        {{ collect([$item->location, $item->supplier?->name])->filter()->implode(' • ') ?: 'No location or supplier set' }}
                                    </p>
                                    @if ($item->description)
                                        <p class="wm-recap-text">{{ $item->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="wm-recap-empty">No timeline items for this day.</div>
                        @endforelse
                    </section>
                @empty
                    <div class="wm-recap-empty">No timeline days available.</div>
                @endforelse
            </article>

            <aside class="wm-recap-card wm-recap-section">
                <p class="wm-recap-label">Checklist texts</p>
                <div class="wm-recap-checklist-list">
                    @forelse ($recapChecklistItems as $item)
                        <article class="wm-recap-checklist-item">
                            <p class="wm-recap-item-title">{!! $item->title ?: 'Checklist item' !!}</p>
                            <p class="wm-recap-meta">
                                {{ collect([$item->supplier?->name, $item->due_date?->format('d/m/Y')])->filter()->implode(' • ') ?: 'Project checklist' }}
                            </p>
                            @if ($item->response)
                                <div class="wm-recap-html">{!! $item->response !!}</div>
                            @elseif ($item->details)
                                <div class="wm-recap-html">{!! $item->details !!}</div>
                            @endif
                        </article>
                    @empty
                        <div class="wm-recap-empty">No checklist texts marked for recap yet.</div>
                    @endforelse
                </div>
            </aside>
        </section>
    </div>
</x-filament-panels::page>
