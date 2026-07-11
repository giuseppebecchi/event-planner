<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $days = $this->getTimelineDays();
        $recapChecklistItems = $this->getRecapChecklistItems();
        $seatingPlans = $this->getRecapSeatingPlans();
        $confirmedSuppliers = $this->getRecapConfirmedSuppliers();
        $railImageUrl = $this->getRecapRailImageUrl();
        $coverActivities = $days
            ->flatMap(fn (array $day) => $day['items'])
            ->filter(fn ($item) => (bool) $item->cover_activity)
            ->values();
        $dateRange = $record->event_start_date
            ? ($record->event_end_date && ! $record->event_start_date->isSameDay($record->event_end_date)
                ? $record->event_start_date->format('F j') . ' - ' . $record->event_end_date->format('F j, Y')
                : $record->event_start_date->format('F j, Y'))
            : ($record->wedding_period ?: 'Date to define');
        $location = collect([$record->locality, $record->region])->filter()->implode(', ');
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
        .wm-recap-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 0.8rem; }
        .wm-recap-image-control {
            display: grid;
            gap: 0.35rem;
            max-width: 28rem;
            padding: 0.75rem 0.9rem;
            border: 1px solid #e3d8ca;
            border-radius: 0.95rem;
            background: #fbf8f4;
        }
        .wm-recap-upload { display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem; color: #4f473f; font-size: 0.78rem; font-weight: 900; }
        .wm-recap-upload-label { letter-spacing: 0.08em; text-transform: uppercase; }
        .wm-recap-help { margin: 0; color: #81776d; font-size: 0.74rem; line-height: 1.35; }
        .wm-recap-file { max-width: 16rem; font-size: 0.78rem; }
        .wm-recap-reset { border: 1px solid #d8ccb9; border-radius: 999px; padding: 0.62rem 0.85rem; background: #fff; color: #5f574f; font-size: 0.78rem; font-weight: 800; cursor: pointer; }
        .wm-recap-preview-frame {
            padding: 2rem clamp(1rem, 4vw, 3.5rem);
            border-radius: 1.2rem;
            background: #eee8df;
            border: 1px solid #e1d7ca;
        }
        .wm-recap-preview-stack {
            display: grid;
            justify-items: center;
            gap: 1.6rem;
            max-width: 66rem;
            margin: 0 auto;
        }
        .wm-recap-pdf-page {
            display: grid;
            grid-template-columns: 7.5rem minmax(0, 1fr);
            width: min(100%, 52rem);
            aspect-ratio: 210 / 297;
            min-height: 42rem;
            border: 1px solid #ded4c9;
            border-radius: 0.5rem;
            overflow: hidden;
            background: #fcfaf6;
            box-shadow: 0 16px 34px rgba(45, 42, 38, 0.08);
        }
        .wm-recap-rail {
            background-image: var(--rail-image);
            background-size: cover;
            background-position: center;
            position: relative;
            min-height: 100%;
        }
        .wm-recap-rail span {
            position: absolute;
            left: 1rem;
            right: 1rem;
            bottom: 1.2rem;
            color: rgba(255,255,255,0.92);
            font-size: 0.62rem;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        .wm-recap-paper { padding: 2.2rem 2.6rem 2.5rem; position: relative; }
        .wm-recap-cover-title { margin: 0; max-width: 30rem; color: #1a1410; font-family: 'Cinzel', serif; font-size: clamp(2.4rem, 5vw, 4rem); line-height: 0.95; }
        .wm-recap-cover-meta { margin-top: 1.35rem; color: #211c17; font-size: 1.2rem; letter-spacing: 0.22em; text-transform: uppercase; }
        .wm-recap-cover-submeta { margin-top: 0.35rem; color: #211c17; font-size: 0.92rem; letter-spacing: 0.18em; text-transform: uppercase; }
        .wm-recap-cover-label { margin-top: 4rem; color: #231d18; font-size: 1rem; font-style: italic; letter-spacing: 0.12em; text-transform: uppercase; }
        .wm-recap-cover-list { margin-top: 1.3rem; }
        .wm-recap-cover-row { display: grid; grid-template-columns: 7rem 2rem 4.5rem minmax(0, 1fr); align-items: center; gap: 1rem; min-height: 4.4rem; }
        .wm-recap-cover-time { text-align: right; color: #1f1914; font-size: 1rem; letter-spacing: 0.08em; }
        .wm-recap-cover-line { width: 2px; height: 100%; background: #26201b; justify-self: center; }
        .wm-recap-cover-icon { width: 3.1rem; height: 3.1rem; object-fit: contain; justify-self: center; }
        .wm-recap-cover-activity { color: #1f1914; font-size: 1rem; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; }
        .wm-recap-section-band { display: inline-block; margin-bottom: 1rem; padding: 0.65rem 0.95rem; background: #efcbbb; color: #261e18; font-size: 0.72rem; font-weight: 900; letter-spacing: 0.16em; text-transform: uppercase; }
        .wm-recap-pdf-day + .wm-recap-pdf-day { margin-top: 1.8rem; }
        .wm-recap-pdf-title { margin: 0; color: #1f1914; font-size: 1.15rem; font-weight: 900; letter-spacing: 0.08em; text-transform: uppercase; }
        .wm-recap-pdf-day .wm-recap-row { grid-template-columns: 6.4rem minmax(0, 1fr); gap: 1rem; border-top: 0; padding: 0.7rem 0; }
        .wm-recap-pdf-day .wm-recap-time { text-align: right; color: #4d4339; }
        .wm-recap-pdf-day .wm-recap-row > div:last-child { border-left: 2px solid #c7a56a; padding-left: 1rem; }
        .wm-recap-seat-card { border-top: 1px solid #e8ded1; padding-top: 1rem; margin-top: 1rem; }
        .wm-recap-seat-card:first-of-type { border-top: 0; margin-top: 0; padding-top: 0; }
        .wm-recap-seat-preview { margin-top: 0.8rem; padding: 0.75rem; background: #fff; border: 1px solid #eadfd2; border-radius: 0.5rem; }
        .wm-recap-seat-preview img { width: 100%; max-height: 26rem; object-fit: contain; }
        .wm-recap-seat-table,
        .wm-recap-suppliers-table { width: 100%; margin-top: 0.8rem; border-collapse: collapse; font-size: 0.82rem; }
        .wm-recap-seat-table td,
        .wm-recap-suppliers-table td,
        .wm-recap-suppliers-table th { padding: 0.55rem 0.65rem; border-bottom: 1px solid #eee4da; text-align: left; vertical-align: top; }
        .wm-recap-suppliers-table th { background: #f1e5d9; color: #342d26; font-size: 0.68rem; letter-spacing: 0.12em; text-transform: uppercase; }
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
            .wm-recap-pdf-page { grid-template-columns: 4.5rem minmax(0, 1fr); }
            .wm-recap-paper { padding: 1.35rem; }
            .wm-recap-preview-frame { padding: 1rem 0.5rem; }
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
                <h2 class="wm-recap-title">PDF-style Preview</h2>
                <p class="wm-recap-copy">Operational preview with the same page flow used in the exported recap PDF.</p>
            </div>
            <div class="wm-recap-toolbar">
                @if (! auth()->user()?->isCustomer())
                    <div class="wm-recap-image-control">
                        <label class="wm-recap-upload">
                            <span class="wm-recap-upload-label">Replace side image</span>
                            <input class="wm-recap-file" type="file" wire:model="recapRailImageUpload" accept="image/*">
                        </label>
                        <p class="wm-recap-help">Recommended: vertical image, 154 x 849 px or same ratio, JPG/PNG. It is cropped to cover the left rail.</p>
                    </div>
                    @if ($recapRailImageUpload)
                        <button type="button" class="wm-recap-reset" wire:click="saveRecapRailImage">Save image</button>
                    @endif
                    @if ($record->recap_left_rail_image_path)
                        <button type="button" class="wm-recap-reset" wire:click="resetRecapRailImage">Use default</button>
                    @endif
                @endif
                <button type="button" class="wm-recap-export" wire:click="exportTimelinePdf">
                    <x-heroicon-o-document-arrow-down />
                    <span>Esporta PDF</span>
                </button>
            </div>
        </section>

        <section class="wm-recap-preview-frame" style="--rail-image: url('{{ $railImageUrl }}')">
            <div class="wm-recap-preview-stack">
                <article class="wm-recap-pdf-page">
                <div class="wm-recap-rail"></div>
                <div class="wm-recap-paper">
                    <h1 class="wm-recap-cover-title">{{ $record->name }}</h1>
                    <div class="wm-recap-cover-meta">{{ $dateRange }}</div>
                    @if ($location)
                        <div class="wm-recap-cover-submeta">{{ $location }}</div>
                    @endif
                    @if ($record->final_guest_count || $record->estimated_guest_count)
                        <div class="wm-recap-meta">{{ $record->final_guest_count ?: $record->estimated_guest_count }} guests</div>
                    @endif
                    <div class="wm-recap-cover-label">Wedding Day Timeline And Info</div>
                    <div class="wm-recap-cover-list">
                        @forelse ($coverActivities as $item)
                            @php
                                $iconFilename = match ($item->cover_activity_type) {
                                    'CEREMONY' => 'ceremony.png',
                                    'PHOTOS' => 'photos.png',
                                    'APERITIVO' => 'aperitivo.png',
                                    'DINNER' => 'dinner.png',
                                    'CAKE CUTTING' => 'cake-cutting.png',
                                    'FIRST DANCE' => 'first-dance.png',
                                    'SEND OFF' => 'send-off.png',
                                    default => null,
                                };
                            @endphp
                            <div class="wm-recap-cover-row">
                                <div class="wm-recap-cover-time">{{ $record->formatTimeForDisplay($item->start_time) ?? '-' }}</div>
                                <div class="wm-recap-cover-line"></div>
                                <div>
                                    @if ($iconFilename)
                                        <img class="wm-recap-cover-icon" src="{{ asset('images/timeline-icons/' . $iconFilename) }}" alt="">
                                    @endif
                                </div>
                                <div>
                                    <div class="wm-recap-cover-activity">{{ $item->cover_activity_type ?: $item->title }}</div>
                                    <div class="wm-recap-meta">{{ collect([$item->location, $item->supplier?->name])->filter()->implode(' • ') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="wm-recap-empty">No cover activities selected yet.</div>
                        @endforelse
                    </div>
                </div>
                </article>

                @forelse ($days as $day)
                    <article class="wm-recap-pdf-page">
                    <div class="wm-recap-rail"><span>{{ $day['date']->format('F j, Y') }}</span></div>
                    <div class="wm-recap-paper">
                        <div class="wm-recap-section-band">Detailed timeline</div>
                        <section class="wm-recap-pdf-day">
                            <h3 class="wm-recap-pdf-title">{{ $day['date']->format('l, F j, Y') }}</h3>
                            @forelse ($day['items'] as $item)
                                <div class="wm-recap-row">
                                    <div class="wm-recap-time">{{ $record->formatTimeForDisplay($item->start_time) ?? '-' }}</div>
                                    <div>
                                        <p class="wm-recap-item-title">{{ $item->title }}</p>
                                        <p class="wm-recap-meta">{{ collect([$item->location, $item->supplier?->name])->filter()->implode(' • ') ?: 'No location or supplier set' }}</p>
                                        @if ($item->description)
                                            <p class="wm-recap-text">{{ $item->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="wm-recap-empty">No timeline items for this day.</div>
                            @endforelse
                        </section>
                    </div>
                    </article>
                @empty
                    <article class="wm-recap-pdf-page">
                    <div class="wm-recap-rail"><span>Timeline recap</span></div>
                    <div class="wm-recap-paper">
                        <div class="wm-recap-section-band">Detailed timeline</div>
                        <div class="wm-recap-empty">No timeline days available.</div>
                    </div>
                    </article>
                @endforelse

                @if ($recapChecklistItems->isNotEmpty())
                    <article class="wm-recap-pdf-page">
                    <div class="wm-recap-rail"><span>Checklist recap</span></div>
                    <div class="wm-recap-paper">
                        <div class="wm-recap-section-band">Checklist recap</div>
                        <div class="wm-recap-checklist-list">
                            @foreach ($recapChecklistItems as $item)
                                <article class="wm-recap-checklist-item">
                                    <p class="wm-recap-item-title">{!! $item->title ?: 'Checklist item' !!}</p>
                                    <p class="wm-recap-meta">{{ collect([$item->supplier?->name, $item->due_date?->format('d/m/Y')])->filter()->implode(' • ') ?: 'Project checklist' }}</p>
                                    @if ($item->response)
                                        <div class="wm-recap-html">{!! $item->response !!}</div>
                                    @elseif ($item->details)
                                        <div class="wm-recap-html">{!! $item->details !!}</div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                    </article>
                @endif

                @foreach ($seatingPlans as $plan)
                    <article class="wm-recap-pdf-page">
                    <div class="wm-recap-rail"><span>{{ \App\Models\ProjectSeatingPlan::PLAN_TYPE_OPTIONS[$plan->plan_type] ?? ($plan->plan_type ?: 'Seating plan') }}</span></div>
                    <div class="wm-recap-paper">
                        <div class="wm-recap-section-band">Seating plan</div>
                        <section class="wm-recap-seat-card">
                            <h3 class="wm-recap-pdf-title">{{ $plan->name }}</h3>
                            <p class="wm-recap-meta">{{ \App\Models\ProjectSeatingPlan::PLAN_TYPE_OPTIONS[$plan->plan_type] ?? ($plan->plan_type ?: 'Layout') }} • {{ $plan->tables->count() }} seating items</p>
                            @if ($plan->preview_image_path)
                                <div class="wm-recap-seat-preview">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($plan->preview_image_path) }}" alt="">
                                </div>
                            @endif
                            @if ($plan->notes)
                                <p class="wm-recap-text">{{ $plan->notes }}</p>
                            @endif
                            @if ($plan->tables->isNotEmpty())
                                <table class="wm-recap-seat-table">
                                    @foreach ($plan->tables as $table)
                                        <tr>
                                            <td>{{ $table->name ?: 'Seating item' }}</td>
                                            <td>{{ $table->table_type ? (\App\Models\ProjectTable::TABLE_TYPE_OPTIONS[$table->table_type] ?? $table->table_type) : 'Seating' }}</td>
                                            <td>{{ $table->seatCount() }} seats</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif
                        </section>
                    </div>
                    </article>
                @endforeach

                <article class="wm-recap-pdf-page">
                    <div class="wm-recap-rail"><span>Confirmed suppliers</span></div>
                    <div class="wm-recap-paper">
                        <div class="wm-recap-section-band">Confirmed suppliers</div>
                        <p class="wm-recap-text">Operational contacts and vendor references for the event day.</p>

                        @if ($confirmedSuppliers->isNotEmpty())
                            <table class="wm-recap-suppliers-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Supplier</th>
                                        <th>Contact</th>
                                        <th>Address / web</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($confirmedSuppliers as $supplier)
                                        <tr>
                                            <td>{{ $supplier['category'] }}</td>
                                            <td>
                                                <strong>{{ $supplier['name'] }}</strong>
                                                @if ($supplier['confirmed_at'])
                                                    <div class="wm-recap-meta">Confirmed {{ $supplier['confirmed_at'] }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                {{ collect([$supplier['contact_person'], $supplier['email'], $supplier['phone']])->filter()->implode(' • ') ?: '—' }}
                                            </td>
                                            <td>
                                                {{ collect([$supplier['address'], $supplier['website']])->filter()->implode(' • ') ?: '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="wm-recap-empty">No confirmed suppliers yet.</div>
                        @endif
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-filament-panels::page>
