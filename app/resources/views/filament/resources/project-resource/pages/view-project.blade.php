<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $budgetSummary = $this->getBudgetSummary();
        $supplierSummary = $this->getSupplierSummary();
        $supplierScoutingSummary = $this->getSupplierScoutingSummary();
        $checklistSummary = $this->getChecklistSummary();
    @endphp

    <style>
        .wm-event-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-event-card {
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

        .wm-event-top-meta span {
            position: relative;
        }

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
            transition: background-color 120ms ease, color 120ms ease;
        }

        .wm-event-countdown-edit:hover {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
        }

        .wm-event-countdown-edit svg {
            width: 1rem;
            height: 1rem;
        }

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

        .wm-event-workspace::-webkit-scrollbar {
            display: none;
        }

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
            transition: background-color 120ms ease, color 120ms ease;
        }

        .wm-event-workspace-link:hover {
            background: rgba(122, 143, 123, 0.10);
            color: #617563;
        }

        .wm-event-workspace-link.is-active {
            background: rgba(122, 143, 123, 0.14);
            color: #2d7a39;
        }

        .wm-event-top-date-tools {
            width: 100%;
        }

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

        .wm-event-date-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }

        .wm-event-anchor {
            position: relative;
            top: -5.5rem;
            height: 0;
        }

        .wm-event-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-event-kpi {
            position: relative;
            overflow: hidden;
            padding: 1.1rem 1.15rem;
        }

        .wm-event-kpi::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.32rem;
            background: var(--kpi-tone, #7a8f7b);
        }

        .wm-event-kpi-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-event-kpi-value {
            margin: 0.55rem 0 0.35rem;
            color: #2d2a26;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .wm-event-kpi-caption {
            margin: 0;
            color: #746d66;
            font-size: 0.88rem;
            line-height: 1.55;
        }

        .wm-event-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(20rem, 0.85fr);
            gap: 1rem;
        }

        .wm-event-stack {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-event-panel {
            padding: 1.2rem 1.25rem;
        }

        .wm-event-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.95rem;
        }

        .wm-event-panel-link {
            color: #2d7a39;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }

        .wm-event-panel-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 1.02rem;
            color: #2d2a26;
        }

        .wm-event-panel-note {
            color: #8b847d;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-event-checklist {
            display: grid;
            gap: 0.7rem;
        }

        .wm-event-check {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-event-check span:first-child {
            color: #453f39;
            font-weight: 600;
        }

        .wm-event-check-mark {
            color: #7a8f7b;
            font-weight: 700;
        }

        .wm-event-check-pending {
            color: #b39b6d;
            font-weight: 700;
        }

        .wm-event-progress-ring {
            width: 10rem;
            height: 10rem;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background:
                radial-gradient(closest-side, #fff 67%, transparent 68% 100%),
                conic-gradient(#ff7f6a {{ $budgetSummary['completion'] }}%, rgba(255, 127, 106, 0.15) 0);
            margin: 0 auto;
        }

        .wm-event-progress-ring-value {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            color: #5f5750;
        }

        .wm-event-budget-grid,
        .wm-event-scout-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .wm-event-budget-grid.is-detailed {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .wm-event-mini {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-event-mini.is-highlighted {
            background: linear-gradient(180deg, #fffaf4 0%, #fbf8f4 100%);
            border-color: #e1d5c8;
        }

        .wm-event-mini-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-event-mini-value {
            margin: 0.45rem 0 0;
            color: #2d2a26;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .wm-event-mini-caption {
            margin: 0.5rem 0 0;
            color: #746d66;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .wm-event-scout-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            margin-top: 1rem;
        }

        .wm-event-scout-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.72rem 0.9rem;
            border-radius: 999px;
            border: 1px solid #ece5dd;
            background: #fbf8f4;
            color: #4f4943;
            font-size: 0.84rem;
            font-weight: 700;
            line-height: 1;
        }

        .wm-event-scout-badge::before {
            content: "";
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 999px;
            background: currentColor;
            box-shadow: 0 0 0 0.18rem rgba(255, 255, 255, 0.9);
        }

        .wm-event-scout-badge.is-confirmed {
            background: rgba(76, 150, 94, 0.10);
            border-color: rgba(76, 150, 94, 0.24);
            color: #2d7a39;
        }

        .wm-event-scout-badge.is-responded {
            background: rgba(214, 166, 64, 0.12);
            border-color: rgba(214, 166, 64, 0.26);
            color: #a57512;
        }

        .wm-event-scout-badge.is-pending {
            background: rgba(194, 91, 75, 0.10);
            border-color: rgba(194, 91, 75, 0.24);
            color: #b54c3d;
        }

        .wm-event-list {
            margin: 0;
            padding-left: 1.3rem;
            display: grid;
            gap: 0.7rem;
            color: #5f5953;
            line-height: 1.65;
        }

        .wm-event-empty {
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px dashed #e1d8cf;
            padding: 1rem 1.05rem;
            color: #8b847d;
        }

        .wm-event-gallery {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-event-gallery-card {
            min-height: 14rem;
            padding: 1rem;
            border-radius: 1.15rem;
            display: flex;
            align-items: flex-end;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.42);
        }

        .wm-event-gallery-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent, rgba(45, 42, 38, 0.28));
        }

        .wm-event-gallery-card.sunrise {
            background: linear-gradient(135deg, #f5d9bf 0%, #f9f2e8 55%, #f0c7a9 100%);
        }

        .wm-event-gallery-card.olive {
            background: linear-gradient(135deg, #dce8d7 0%, #f6f7f1 50%, #9db68d 100%);
        }

        .wm-event-gallery-card.sky {
            background: linear-gradient(135deg, #d8e8f2 0%, #f7fafc 52%, #c2d7e8 100%);
        }

        .wm-event-gallery-card.sand {
            background: linear-gradient(135deg, #efe1d2 0%, #faf5ef 48%, #e2c6ab 100%);
        }

        .wm-event-gallery-copy {
            position: relative;
            z-index: 1;
        }

        .wm-event-gallery-title {
            margin: 0 0 0.4rem;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: 1rem;
        }

        .wm-event-gallery-text {
            margin: 0;
            color: rgba(45, 42, 38, 0.82);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        @media (max-width: 1280px) {
            .wm-event-grid {
                grid-template-columns: 1fr;
            }

            .wm-event-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .wm-event-gallery {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .wm-event-top-head,
            .wm-event-top-side,
            .wm-event-date-grid {
                grid-template-columns: 1fr;
                justify-content: flex-start;
            }
        }

        @media (max-width: 768px) {
            .wm-event-kpis,
            .wm-event-budget-grid,
            .wm-event-scout-grid,
            .wm-event-gallery {
                grid-template-columns: 1fr;
            }

            .wm-event-budget-grid.is-detailed {
                grid-template-columns: 1fr;
            }

            .wm-event-top-side {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 0.7rem;
            }

            .wm-event-summary-chip,
            .wm-event-countdown {
                width: 100%;
            }
        }
    </style>

    <div class="wm-event-dashboard">
        <div id="calendar" class="wm-event-anchor"></div>
        <div id="layout-seating" class="wm-event-anchor"></div>

        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'dashboard',
        ])

        <section class="wm-event-kpis" id="guests">
            @foreach ($this->getPlanningHighlights() as $highlight)
                <article class="wm-event-card wm-event-kpi" style="--kpi-tone:
                    {{ match($highlight['tone']) {
                        'blue' => '#2e4a62',
                        'gold' => '#c9a96a',
                        'rose' => '#e3b7b2',
                        default => '#7a8f7b',
                    } }}">
                    <p class="wm-event-kpi-label">{{ $highlight['label'] }}</p>
                    <p class="wm-event-kpi-value">{{ $highlight['value'] }}</p>
                    <p class="wm-event-kpi-caption">{{ $highlight['caption'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="wm-event-grid">
            <div class="wm-event-stack">
                <article class="wm-event-card wm-event-panel" id="timeline">
                    <div class="wm-event-panel-header">
                        <h3 class="wm-event-panel-title">Supplier scouting</h3>
                        <span class="wm-event-panel-note">{{ $supplierScoutingSummary['confirmed_count'] }} / {{ $supplierScoutingSummary['categories_count'] }} covered items</span>
                    </div>

                    <div class="wm-event-scout-grid">
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Confirmed items</p>
                            <p class="wm-event-mini-value">{{ $supplierScoutingSummary['confirmed_count'] }}</p>
                        </div>
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Items with responses</p>
                            <p class="wm-event-mini-value">{{ $supplierScoutingSummary['responded_count'] }}</p>
                        </div>
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Needs work</p>
                            <p class="wm-event-mini-value">{{ $supplierScoutingSummary['pending_count'] }}</p>
                        </div>
                    </div>

                    <div class="wm-event-scout-grid">
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Awaiting</p>
                            <p class="wm-event-mini-value">{{ $supplierSummary['awaiting'] }}</p>
                        </div>
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Responses received</p>
                            <p class="wm-event-mini-value">{{ $supplierSummary['received'] }}</p>
                        </div>
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Confirmed proposals</p>
                            <p class="wm-event-mini-value">{{ $supplierSummary['confirmed'] }}</p>
                        </div>
                    </div>

                    <div class="wm-event-scout-badges">
                        @foreach ($supplierScoutingSummary['items'] as $item)
                            <span class="wm-event-scout-badge {{ match($item['status']) {
                                'confirmed' => 'is-confirmed',
                                'responded' => 'is-responded',
                                default => 'is-pending',
                            } }}">
                                {{ $item['label'] }}
                            </span>
                        @endforeach
                    </div>
                </article>

                <article class="wm-event-card wm-event-panel" id="checklist">
                    <div class="wm-event-panel-header">
                        <h3 class="wm-event-panel-title">Checklist overview</h3>
                        <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('checklist', ['record' => $record]) }}" class="wm-event-panel-link">Open checklist</a>
                    </div>

                    <div class="wm-event-budget-grid">
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Checklist groups</p>
                            <p class="wm-event-mini-value">{{ $checklistSummary['groups'] }}</p>
                            <p class="wm-event-mini-caption">{{ $checklistSummary['total'] }} total items</p>
                        </div>
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Enabled by default</p>
                            <p class="wm-event-mini-value">{{ $checklistSummary['enabled'] }}</p>
                            <p class="wm-event-mini-caption">{{ $checklistSummary['optional'] }} optional items</p>
                        </div>
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Scheduled items</p>
                            <p class="wm-event-mini-value">{{ $checklistSummary['dated'] }}</p>
                            <p class="wm-event-mini-caption">{{ $checklistSummary['due_soon'] }} due in the next 30 days</p>
                        </div>
                    </div>

                    <div class="wm-event-checklist">
                        @foreach ($this->getPreparationItems() as $item)
                            <div class="wm-event-check">
                                <span>{{ $item['label'] }}</span>
                                <span class="{{ $item['done'] ? 'wm-event-check-mark' : 'wm-event-check-pending' }}">
                                    {{ $item['done'] ? 'Done' : 'Pending' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </article>

            </div>

            <div class="wm-event-stack">
                <article class="wm-event-card wm-event-panel" id="budget">
                    <div class="wm-event-panel-header">
                        <h3 class="wm-event-panel-title">Budget progress</h3>
                        <span class="wm-event-panel-note">{{ $budgetSummary['confirmed_count'] }} / {{ $budgetSummary['categories_count'] }} confirmed</span>
                    </div>

                    <div class="wm-event-progress-ring">
                        <span class="wm-event-progress-ring-value">{{ $budgetSummary['completion'] }}%</span>
                    </div>

                    <div class="wm-event-budget-grid is-detailed">
                        <div class="wm-event-mini is-highlighted">
                            <p class="wm-event-mini-label">Updated total budget</p>
                            <p class="wm-event-mini-value">EUR {{ number_format($budgetSummary['comparison_total'], 2, ',', '.') }}</p>
                            <p class="wm-event-mini-caption">Vs hypothetical total: EUR {{ number_format($budgetSummary['estimated_total'], 2, ',', '.') }}</p>
                        </div>
                        <div class="wm-event-mini is-highlighted">
                            <p class="wm-event-mini-label">Confirmed items</p>
                            <p class="wm-event-mini-value">EUR {{ number_format($budgetSummary['final_total'], 2, ',', '.') }}</p>
                            <p class="wm-event-mini-caption">Vs hypothetical confirmed total: EUR {{ number_format($budgetSummary['confirmed_hypothetical_total'], 2, ',', '.') }}</p>
                        </div>
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Confirmed items count</p>
                            <p class="wm-event-mini-value">{{ $budgetSummary['confirmed_count'] }}</p>
                            <p class="wm-event-mini-caption">Out of {{ $budgetSummary['categories_count'] }} wedding items</p>
                        </div>
                        <div class="wm-event-mini">
                            <p class="wm-event-mini-label">Items in evaluation</p>
                            <p class="wm-event-mini-value">{{ $budgetSummary['in_evaluation_count'] }}</p>
                            <p class="wm-event-mini-caption">{{ $budgetSummary['categories_count'] - $budgetSummary['confirmed_count'] - $budgetSummary['in_evaluation_count'] }} still hypothetical</p>
                        </div>
                    </div>
                </article>

                <article class="wm-event-card wm-event-panel" id="notes">
                    <div class="wm-event-panel-header">
                        <h3 class="wm-event-panel-title">My to-dos</h3>
                        <span class="wm-event-panel-note">Next actions</span>
                    </div>

                    <ol class="wm-event-list">
                        @foreach ($this->getTodoItems() as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ol>
                </article>

                <article class="wm-event-card wm-event-panel" id="contacts">
                    <div class="wm-event-panel-header">
                        <h3 class="wm-event-panel-title">Important items</h3>
                        <span class="wm-event-panel-note">Watchlist</span>
                    </div>

                    @if (count($this->getImportantItems()))
                        <ul class="wm-event-list">
                            @foreach ($this->getImportantItems() as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="wm-event-empty">No important items detected.</div>
                    @endif
                </article>
            </div>
        </section>

        <section class="wm-event-stack">
            <article class="wm-event-card wm-event-panel" id="moodboard">
                <div class="wm-event-panel-header">
                    <h3 class="wm-event-panel-title">Moodboard</h3>
                    <span class="wm-event-panel-note">Initial placeholders</span>
                </div>

                <div class="wm-event-gallery">
                    @foreach ($this->getInspirationTiles() as $tile)
                        <article class="wm-event-gallery-card {{ $tile['tone'] }}">
                            <div class="wm-event-gallery-copy">
                                <h4 class="wm-event-gallery-title">{{ $tile['title'] }}</h4>
                                <p class="wm-event-gallery-text">{{ $tile['caption'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
