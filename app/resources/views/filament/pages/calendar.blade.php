<x-filament-panels::page>
    @php
        $cells = $this->getCalendarCells();
        $listItems = $this->getListItems();
        $selectedItem = $this->getSelectedCalendarItem();
        $weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $monthOptions = $this->getMonthPickerOptions();
        $yearOptions = $this->getMonthPickerYearOptions();
        $overdue = $this->getOverdueSummary();
        $dueSoon = $this->getDueSoonSummary();
        $nextPayments = $this->getNextPaymentDeadlines();
        $nextChecklists = $this->getNextChecklistDeadlines();
    @endphp

    <style>
        .wm-master-calendar-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        [x-cloak] { display: none !important; }

        .wm-master-card {
            border: 1px solid #e8e3dc;
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 20px 42px rgba(45, 42, 38, 0.06);
        }

        .wm-master-hero {
            position: relative;
            overflow: hidden;
            padding: 1.3rem 1.35rem 1.4rem;
            background:
                radial-gradient(circle at top right, rgba(93, 143, 183, 0.14), transparent 26%),
                linear-gradient(135deg, rgba(252, 248, 243, 0.98), rgba(247, 243, 237, 0.98));
        }

        .wm-master-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: end;
        }

        .wm-master-kicker {
            margin: 0;
            color: #8f6d29;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .wm-master-title {
            margin: 0.35rem 0 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.55rem, 3vw, 2.35rem);
            line-height: 1.06;
        }

        .wm-master-copy {
            max-width: 46rem;
            margin: 0.65rem 0 0;
            color: #746d66;
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .wm-master-hero-stats {
            display: inline-flex;
            gap: 0.7rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .wm-master-hero-chip {
            min-width: 8rem;
            padding: 0.8rem 0.92rem;
            border-radius: 1rem;
            border: 1px solid rgba(201, 169, 106, 0.18);
            background: rgba(255, 255, 255, 0.82);
        }

        .wm-master-hero-chip-label {
            margin: 0;
            color: #94887d;
            font-size: 0.64rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-master-hero-chip-value {
            margin: 0.28rem 0 0;
            color: #2d2a26;
            font-size: 1.08rem;
            font-weight: 700;
        }

        .wm-master-alerts {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-master-alert {
            padding: 1.05rem 1.15rem;
        }

        .wm-master-alert.is-danger {
            background: linear-gradient(135deg, rgba(249, 240, 239, 0.96), rgba(255, 255, 255, 0.96));
        }

        .wm-master-alert.is-warning {
            background: linear-gradient(135deg, rgba(250, 246, 237, 0.96), rgba(255, 255, 255, 0.96));
        }

        .wm-master-alert-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-master-alert-title {
            margin: 0.45rem 0 0;
            color: #2d2a26;
            font-size: 1.08rem;
            font-weight: 700;
        }

        .wm-master-alert-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }

        .wm-master-alert-stat {
            color: #5f5953;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .wm-master-alert-list {
            display: grid;
            gap: 0.55rem;
            margin-top: 0.9rem;
        }

        .wm-master-alert-item {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.7rem 0.8rem;
            border-radius: 0.95rem;
            background: rgba(255, 255, 255, 0.78);
        }

        .wm-master-alert-item-title,
        .wm-master-alert-item-meta {
            margin: 0;
            line-height: 1.45;
        }

        .wm-master-alert-item-title {
            color: #2d2a26;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .wm-master-alert-item-meta {
            color: #80776f;
            font-size: 0.76rem;
        }

        .wm-master-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(20rem, 0.72fr);
            gap: 1rem;
            align-items: start;
        }

        .wm-calendar-shell,
        .wm-master-panel {
            padding: 1.1rem 1.2rem;
        }

        .wm-calendar-shell {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-calendar-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.9rem;
        }

        .wm-calendar-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .wm-calendar-month-picker {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .wm-calendar-nav-button,
        .wm-calendar-view-button {
            border: 1px solid #e2d8ca;
            background: #fbf8f4;
            color: #5f5953;
            min-height: 2.5rem;
            padding: 0 0.9rem;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
        }

        .wm-calendar-view-button.is-active {
            background: rgba(122, 143, 123, 0.14);
            border-color: rgba(122, 143, 123, 0.22);
            color: #2d7a39;
        }

        .wm-calendar-month-label {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 10rem;
            min-height: 2.85rem;
            padding: 0 1.05rem;
            border: 1px solid #dfd3c4;
            border-radius: 999px;
            background: linear-gradient(180deg, #fffdfa 0%, #f8f4ee 100%);
            color: #2d2a26;
            font-size: 1.12rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(45, 42, 38, 0.06);
        }

        .wm-calendar-month-label-caret { width: 0.9rem; height: 0.9rem; color: #b89452; }

        .wm-calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .wm-calendar-legend-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 1.9rem;
            padding: 0 0.75rem;
            border-radius: 999px;
            background: #f6f1e8;
            color: #605951;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .wm-calendar-legend-dot,
        .wm-dot-olive,
        .wm-dot-sky,
        .wm-dot-rose {
            width: 0.72rem;
            height: 0.72rem;
            border-radius: 999px;
            display: inline-block;
        }

        .wm-dot-olive { background: #87985e; }
        .wm-dot-sky { background: #5d8fb7; }
        .wm-dot-rose { background: #c57f88; }

        .wm-calendar-month-popup {
            position: absolute;
            top: calc(100% + 0.85rem);
            left: 50%;
            z-index: 20;
            width: min(32rem, calc(100vw - 2rem));
            transform: translateX(-50%);
            padding: 1.2rem;
            border: 1px solid #d8ccbd;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 20px 50px rgba(32, 24, 18, 0.16);
        }

        .wm-calendar-month-popup-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 7rem auto;
            gap: 0.65rem;
            align-items: center;
        }

        .wm-calendar-month-select,
        .wm-calendar-month-year {
            width: 100%;
            min-height: 3.2rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0 0.95rem;
            color: #4f4943;
            font-size: 1rem;
        }

        .wm-calendar-month-go,
        .wm-calendar-month-action {
            min-height: 3.1rem;
            padding: 0 1rem;
            border: 0;
            background: #b89452;
            color: #fff;
            font-size: 0.96rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-calendar-month-copy {
            margin: 1rem 0 0.8rem;
            color: #6c645d;
            font-size: 0.9rem;
            line-height: 1.6;
            text-align: center;
        }

        .wm-calendar-month-actions {
            display: grid;
            gap: 0.8rem;
        }

        .wm-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .wm-calendar-weekday {
            color: #8b847d;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0 0.25rem;
        }

        .wm-calendar-day {
            min-height: 11rem;
            border-radius: 1.05rem;
            border: 1px solid #ece5dd;
            background: #fffdfa;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .wm-calendar-day.is-outside {
            background: #f8f5f1;
            color: #b2aaa2;
        }

        .wm-calendar-day-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .wm-calendar-day-number {
            color: #2d2a26;
            font-size: 0.94rem;
            font-weight: 700;
        }

        .wm-calendar-items {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .wm-calendar-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.45rem;
            align-items: start;
            padding: 0.45rem 0.55rem;
            border-radius: 0.85rem;
            color: #fff;
            font-size: 0.76rem;
            line-height: 1.35;
            border: 0;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .wm-calendar-item.is-olive { background: #87985e; }
        .wm-calendar-item.is-sky { background: #5d8fb7; }
        .wm-calendar-item.is-rose { background: #c57f88; }
        .wm-calendar-item.is-dimmed { opacity: 0.58; }

        .wm-calendar-item-check {
            width: 0.95rem;
            height: 0.95rem;
            margin-top: 0.08rem;
            accent-color: #fff;
        }

        .wm-calendar-item-title,
        .wm-calendar-item-project,
        .wm-calendar-item-time {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wm-calendar-item-title { font-weight: 700; }
        .wm-calendar-item-project,
        .wm-calendar-item-time { opacity: 0.88; font-size: 0.72rem; }

        .wm-calendar-more {
            border: 0;
            background: transparent;
            color: #8f6d29;
            font-size: 0.76rem;
            font-weight: 700;
            text-align: left;
            padding: 0.1rem 0;
            cursor: pointer;
        }

        .wm-calendar-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .wm-calendar-list-item,
        .wm-master-list-item {
            display: grid;
            gap: 0.85rem;
            align-items: start;
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            border: 1px solid #ece5dd;
            background: #fffdfa;
        }

        .wm-calendar-list-item {
            grid-template-columns: auto minmax(0, 1fr) auto;
        }

        .wm-calendar-list-marker {
            width: 0.9rem;
            height: 0.9rem;
            margin-top: 0.3rem;
            border-radius: 999px;
        }

        .wm-calendar-list-copy {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            min-width: 0;
        }

        .wm-calendar-list-title,
        .wm-master-list-title {
            margin: 0;
            color: #2d2a26;
            font-size: 0.96rem;
            font-weight: 700;
        }

        .wm-calendar-list-meta,
        .wm-calendar-list-text,
        .wm-master-list-meta {
            margin: 0;
            color: #746d66;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .wm-calendar-list-program {
            margin-top: 0.35rem;
            padding: 0.75rem 0.9rem;
            border-radius: 0.9rem;
            background: #f8f5f1;
            color: #4f4943;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .wm-calendar-list-side {
            color: #5f5953;
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .wm-master-side {
            display: grid;
            gap: 1rem;
        }

        .wm-master-panel-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.9rem;
        }

        .wm-master-panel-title {
            margin: 0;
            color: #2d2a26;
            font-size: 1rem;
            font-weight: 700;
        }

        .wm-master-panel-copy {
            margin: 0.3rem 0 0;
            color: #746d66;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .wm-master-list {
            display: grid;
            gap: 0.7rem;
        }

        .wm-master-list-item {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .wm-master-list-date {
            color: #5f5953;
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .wm-master-link {
            color: #8f6d29;
            font-weight: 700;
            text-decoration: none;
        }

        .wm-calendar-detail-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(31, 25, 20, 0.18);
        }

        .wm-calendar-detail {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 50;
            width: min(36rem, calc(100vw - 2rem));
            transform: translate(-50%, -50%);
            border-radius: 1.1rem;
            border: 1px solid #dfd3c4;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 24px 60px rgba(24, 18, 14, 0.2);
            padding: 1.2rem;
        }

        .wm-calendar-detail-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-calendar-detail-title { margin: 0; color: #2d2a26; font-size: 1.08rem; font-weight: 700; }
        .wm-calendar-detail-meta,
        .wm-calendar-detail-text { margin: 0.45rem 0 0; color: #746d66; font-size: 0.88rem; line-height: 1.6; }

        .wm-calendar-detail-close {
            border: 0;
            background: #f6f1e8;
            color: #5f5953;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 999px;
            cursor: pointer;
        }

        .wm-calendar-detail-program {
            margin-top: 0.9rem;
            padding: 0.85rem 0.95rem;
            border-radius: 0.95rem;
            background: #f8f5f1;
            color: #4f4943;
            font-size: 0.86rem;
            line-height: 1.6;
        }

        .wm-calendar-empty {
            padding: 1rem 1.1rem;
            color: #746d66;
            font-size: 0.9rem;
        }

        @media (max-width: 1180px) {
            .wm-master-layout,
            .wm-master-alerts {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 860px) {
            .wm-master-hero-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .wm-master-hero-stats {
                justify-content: start;
            }

            .wm-calendar-grid {
                gap: 0.5rem;
            }

            .wm-calendar-day {
                min-height: 8.8rem;
                padding: 0.6rem;
            }
        }

        @media (max-width: 760px) {
            .wm-calendar-month-popup {
                width: min(28rem, calc(100vw - 1.25rem));
            }

            .wm-calendar-month-popup-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .wm-calendar-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .wm-calendar-weekday {
                display: none;
            }

            .wm-calendar-list-item {
                grid-template-columns: auto minmax(0, 1fr);
            }

            .wm-calendar-list-side {
                grid-column: 2;
            }
        }
    </style>

    <div class="wm-master-calendar-page">
        <section class="wm-master-card wm-master-hero">
            <div class="wm-master-hero-grid">
                    <div>
                        <p class="wm-master-kicker">Planner control room</p>
                        <h1 class="wm-master-title">Planner Calendar</h1>
                        <p class="wm-master-copy">The planner's master calendar to keep payment deadlines, checklist due dates and project events under control across all active weddings, with every item always tied back to its event.</p>
                    </div>

                <div class="wm-master-hero-stats">
                    <div class="wm-master-hero-chip">
                        <p class="wm-master-hero-chip-label">Overdue</p>
                        <p class="wm-master-hero-chip-value">{{ $overdue['payments_count'] + $overdue['checklists_count'] }}</p>
                    </div>
                    <div class="wm-master-hero-chip">
                        <p class="wm-master-hero-chip-label">Next 7 days</p>
                        <p class="wm-master-hero-chip-value">{{ $dueSoon['payments_count'] + $dueSoon['checklists_count'] }}</p>
                    </div>
                    <div class="wm-master-hero-chip">
                        <p class="wm-master-hero-chip-label">Upcoming payments</p>
                        <p class="wm-master-hero-chip-value">{{ $nextPayments->count() }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="wm-master-alerts">
            <article class="wm-master-card wm-master-alert is-danger">
                <p class="wm-master-alert-label">Criticalities</p>
                <h2 class="wm-master-alert-title">Overdue items requiring attention</h2>
                <div class="wm-master-alert-stats">
                    <span class="wm-master-alert-stat">{{ $overdue['payments_count'] }} overdue payments</span>
                    <span class="wm-master-alert-stat">{{ $overdue['checklists_count'] }} overdue checklist items</span>
                </div>

                <div class="wm-master-alert-list">
                    @forelse ($overdue['items'] as $item)
                        <button type="button" class="wm-master-alert-item" wire:click="openCalendarItem('{{ $item['kind'] }}', {{ $item['id'] }})">
                            <div>
                                <p class="wm-master-alert-item-title">{{ $item['title'] }}</p>
                                <p class="wm-master-alert-item-meta">{{ $item['project_name'] }}</p>
                            </div>
                            <p class="wm-master-alert-item-meta">{{ $item['start_date']->format('M j, Y') }}</p>
                        </button>
                    @empty
                        <div class="wm-calendar-empty">No overdue payments or checklist items.</div>
                    @endforelse
                </div>
            </article>

            <article class="wm-master-card wm-master-alert is-warning">
                <p class="wm-master-alert-label">Imminent</p>
                <h2 class="wm-master-alert-title">Items due in the next 7 days</h2>
                <div class="wm-master-alert-stats">
                    <span class="wm-master-alert-stat">{{ $dueSoon['payments_count'] }} payments coming up</span>
                    <span class="wm-master-alert-stat">{{ $dueSoon['checklists_count'] }} checklist items coming up</span>
                </div>

                <div class="wm-master-alert-list">
                    @forelse ($dueSoon['items'] as $item)
                        <button type="button" class="wm-master-alert-item" wire:click="openCalendarItem('{{ $item['kind'] }}', {{ $item['id'] }})">
                            <div>
                                <p class="wm-master-alert-item-title">{{ $item['title'] }}</p>
                                <p class="wm-master-alert-item-meta">{{ $item['project_name'] }}</p>
                            </div>
                            <p class="wm-master-alert-item-meta">{{ $item['start_date']->format('M j, Y') }}</p>
                        </button>
                    @empty
                        <div class="wm-calendar-empty">No payment or checklist deadlines in the next 7 days.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="wm-master-layout">
            <article class="wm-master-card wm-calendar-shell">
                <div class="wm-calendar-toolbar">
                    <div class="wm-calendar-nav">
                        <button type="button" class="wm-calendar-nav-button" wire:click="previousMonth">Prev</button>
                        <div class="wm-calendar-month-picker" x-data="{ openMonthPicker: false }">
                            <button type="button" class="wm-calendar-month-label" x-on:click="openMonthPicker = ! openMonthPicker">
                                <span>{{ $this->getMonthLabel() }}</span>
                                <x-heroicon-o-chevron-down class="wm-calendar-month-label-caret" />
                            </button>

                            <div class="wm-calendar-month-popup" x-cloak x-show="openMonthPicker" x-transition.opacity x-on:click.outside="openMonthPicker = false">
                                <div class="wm-calendar-month-popup-grid">
                                    <select class="wm-calendar-month-select" wire:model="monthPickerForm.month">
                                        @foreach ($monthOptions as $monthNumber => $monthLabel)
                                            <option value="{{ $monthNumber }}">{{ $monthLabel }}</option>
                                        @endforeach
                                    </select>

                                    <input type="number" min="{{ min($yearOptions) }}" max="{{ max($yearOptions) }}" class="wm-calendar-month-year" wire:model="monthPickerForm.year">

                                    <button type="button" class="wm-calendar-month-go" wire:click="goToSelectedMonth" x-on:click="openMonthPicker = false">Go</button>
                                </div>

                                <p class="wm-calendar-month-copy">Jump across planner months and review all active weddings in the same visual calendar.</p>

                                <div class="wm-calendar-month-actions">
                                    <button type="button" class="wm-calendar-month-action" wire:click="goToToday" x-on:click="openMonthPicker = false">
                                        Go to Today
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="wm-calendar-nav-button" wire:click="nextMonth">Next</button>
                    </div>

                    <div class="wm-calendar-legend">
                        <span class="wm-calendar-legend-chip"><span class="wm-calendar-legend-dot wm-dot-olive"></span>Checklist</span>
                        <span class="wm-calendar-legend-chip"><span class="wm-calendar-legend-dot wm-dot-sky"></span>Payments</span>
                        <span class="wm-calendar-legend-chip"><span class="wm-calendar-legend-dot wm-dot-rose"></span>Events</span>
                    </div>

                    <div class="wm-calendar-nav">
                        <button type="button" class="wm-calendar-view-button {{ $calendarView === 'month' ? 'is-active' : '' }}" wire:click="setCalendarView('month')">Month</button>
                        <button type="button" class="wm-calendar-view-button {{ $calendarView === 'list' ? 'is-active' : '' }}" wire:click="setCalendarView('list')">List</button>
                    </div>
                </div>

                @if ($calendarView === 'month')
                    <div class="wm-calendar-grid">
                        @foreach ($weekdayLabels as $label)
                            <div class="wm-calendar-weekday">{{ $label }}</div>
                        @endforeach

                        @foreach ($cells as $cell)
                            <div class="wm-calendar-day {{ $cell['is_current_month'] ? '' : 'is-outside' }}" x-data="{ expanded: false }">
                                <div class="wm-calendar-day-head">
                                    <span class="wm-calendar-day-number">{{ $cell['date']->format('j') }}</span>
                                </div>

                                <div class="wm-calendar-items">
                                    @forelse ($cell['items']->take(4) as $item)
                                        <button type="button" class="wm-calendar-item is-{{ $item['color'] }} {{ $item['completed'] ? 'is-dimmed' : '' }}" wire:click.stop="openCalendarItem('{{ $item['kind'] }}', {{ $item['id'] }})" title="{{ $item['project_name'] }} · {{ $item['title'] }}">
                                            @if ($item['kind'] === 'checklist')
                                                <input
                                                    type="checkbox"
                                                    class="wm-calendar-item-check"
                                                    @checked($item['completed'])
                                                    wire:click.stop="toggleChecklistCompleted({{ $item['id'] }}, $event.target.checked)"
                                                >
                                            @else
                                                <span class="wm-calendar-legend-dot wm-dot-{{ $item['color'] }}"></span>
                                            @endif

                                            <span>
                                                <span class="wm-calendar-item-title">{{ $item['title'] }}</span>
                                                <span class="wm-calendar-item-project">{{ $item['project_name'] }}</span>
                                                @if ($item['kind'] === 'event' && ! $item['is_all_day'])
                                                    <span class="wm-calendar-item-time">{{ $item['starts_at']->format('H:i') }}</span>
                                                @endif
                                            </span>
                                        </button>
                                    @empty
                                    @endforelse

                                    @if ($cell['items']->count() > 4)
                                        <button type="button" class="wm-calendar-more" x-show="!expanded" x-on:click="expanded = true">
                                            + {{ $cell['items']->count() - 4 }} more events
                                        </button>

                                        <template x-if="expanded">
                                            <div class="wm-calendar-items">
                                                @foreach ($cell['items']->slice(4) as $item)
                                                    <button type="button" class="wm-calendar-item is-{{ $item['color'] }} {{ $item['completed'] ? 'is-dimmed' : '' }}" wire:click.stop="openCalendarItem('{{ $item['kind'] }}', {{ $item['id'] }})" title="{{ $item['project_name'] }} · {{ $item['title'] }}">
                                                        @if ($item['kind'] === 'checklist')
                                                            <input
                                                                type="checkbox"
                                                                class="wm-calendar-item-check"
                                                                @checked($item['completed'])
                                                                wire:click.stop="toggleChecklistCompleted({{ $item['id'] }}, $event.target.checked)"
                                                            >
                                                        @else
                                                            <span class="wm-calendar-legend-dot wm-dot-{{ $item['color'] }}"></span>
                                                        @endif

                                                        <span>
                                                            <span class="wm-calendar-item-title">{{ $item['title'] }}</span>
                                                            <span class="wm-calendar-item-project">{{ $item['project_name'] }}</span>
                                                            @if ($item['kind'] === 'event' && ! $item['is_all_day'])
                                                                <span class="wm-calendar-item-time">{{ $item['starts_at']->format('H:i') }}</span>
                                                            @endif
                                                        </span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </template>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="wm-calendar-list">
                        @forelse ($listItems as $item)
                            <article class="wm-calendar-list-item">
                                <span class="wm-calendar-list-marker wm-dot-{{ $item['color'] }}"></span>

                                <div class="wm-calendar-list-copy">
                                    <p class="wm-calendar-list-title">{{ $item['title'] }}</p>
                                    <p class="wm-calendar-list-meta">
                                        {{ ucfirst($item['kind']) }}
                                        · {{ $item['project_name'] }}
                                        · {{ $item['start_date']->format('F j, Y') }}
                                        @if ($item['kind'] === 'event' && $item['end_date']->ne($item['start_date']))
                                            - {{ $item['end_date']->format('F j, Y') }}
                                        @endif
                                    </p>

                                    @if (filled($item['subtitle']))
                                        <p class="wm-calendar-list-text">{{ $item['subtitle'] }}</p>
                                    @endif

                                    @if (filled($item['program_html']))
                                        <div class="wm-calendar-list-program">{!! $item['program_html'] !!}</div>
                                    @endif

                                    <p class="wm-calendar-list-text"><a href="{{ $item['project_dashboard_url'] }}" class="wm-master-link">Open event</a></p>
                                </div>

                                <div class="wm-calendar-list-side">
                                    @if ($item['kind'] === 'event' && ! $item['is_all_day'])
                                        {{ $item['starts_at']->format('H:i') }}{{ $item['ends_at'] ? ' - ' . $item['ends_at']->format('H:i') : '' }}
                                    @else
                                        All day
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="wm-calendar-empty">No calendar items found.</div>
                        @endforelse
                    </div>
                @endif
            </article>

            <aside class="wm-master-side">
                <section class="wm-master-card wm-master-panel">
                    <div class="wm-master-panel-head">
                        <div>
                            <h2 class="wm-master-panel-title">Next 10 Payment Deadlines</h2>
                            <p class="wm-master-panel-copy">Upcoming unpaid supplier payments across all active events.</p>
                        </div>
                    </div>

                    <div class="wm-master-list">
                        @forelse ($nextPayments as $item)
                            <button type="button" class="wm-master-list-item" wire:click="openCalendarItem('{{ $item['kind'] }}', {{ $item['id'] }})">
                                <div>
                                    <p class="wm-master-list-title">{{ $item['title'] }}</p>
                                    <p class="wm-master-list-meta">{{ $item['project_name'] }} · {{ $item['subtitle'] }}</p>
                                </div>
                                <div class="wm-master-list-date">{{ $item['start_date']->format('M j, Y') }}</div>
                            </button>
                        @empty
                            <div class="wm-calendar-empty">No upcoming unpaid payments.</div>
                        @endforelse
                    </div>
                </section>

                <section class="wm-master-card wm-master-panel">
                    <div class="wm-master-panel-head">
                        <div>
                            <h2 class="wm-master-panel-title">Next 10 Checklist Deadlines</h2>
                            <p class="wm-master-panel-copy">Upcoming open checklist items across all active events.</p>
                        </div>
                    </div>

                    <div class="wm-master-list">
                        @forelse ($nextChecklists as $item)
                            <button type="button" class="wm-master-list-item" wire:click="openCalendarItem('{{ $item['kind'] }}', {{ $item['id'] }})">
                                <div>
                                    <p class="wm-master-list-title">{{ $item['title'] }}</p>
                                    <p class="wm-master-list-meta">{{ $item['project_name'] }}</p>
                                </div>
                                <div class="wm-master-list-date">{{ $item['start_date']->format('M j, Y') }}</div>
                            </button>
                        @empty
                            <div class="wm-calendar-empty">No upcoming checklist deadlines.</div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </section>

        @if ($selectedItem)
            <div class="wm-calendar-detail-backdrop" wire:click="closeCalendarItem"></div>
            <div class="wm-calendar-detail" role="dialog" aria-modal="true">
                <div class="wm-calendar-detail-head">
                    <div>
                        <h3 class="wm-calendar-detail-title">{{ $selectedItem['title'] }}</h3>
                        <p class="wm-calendar-detail-meta">
                            {{ match($selectedItem['kind']) {
                                'checklist' => 'Checklist item',
                                'payment' => 'Payment deadline',
                                default => 'Project event',
                            } }}
                            · {{ $selectedItem['project_name'] }}
                            · {{ $selectedItem['start_date']->format('F j, Y') }}
                            @if ($selectedItem['kind'] === 'event' && $selectedItem['end_date']->ne($selectedItem['start_date']))
                                - {{ $selectedItem['end_date']->format('F j, Y') }}
                            @endif
                            @if ($selectedItem['kind'] === 'event' && ! $selectedItem['is_all_day'])
                                · {{ $selectedItem['starts_at']->format('H:i') }}{{ $selectedItem['ends_at'] ? ' - ' . $selectedItem['ends_at']->format('H:i') : '' }}
                            @elseif ($selectedItem['kind'] === 'event')
                                · All day
                            @endif
                        </p>
                        <p class="wm-calendar-detail-text">
                            <a href="{{ $selectedItem['project_dashboard_url'] }}" class="wm-master-link">Open event dashboard</a>
                            ·
                            <a href="{{ $selectedItem['project_url'] }}" class="wm-master-link">Open event calendar</a>
                        </p>
                    </div>

                    <button type="button" class="wm-calendar-detail-close" wire:click="closeCalendarItem">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>

                @if (filled($selectedItem['subtitle']))
                    <p class="wm-calendar-detail-text">{{ $selectedItem['subtitle'] }}</p>
                @endif

                @if (filled($selectedItem['program_html']))
                    <div class="wm-calendar-detail-program">{!! $selectedItem['program_html'] !!}</div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
