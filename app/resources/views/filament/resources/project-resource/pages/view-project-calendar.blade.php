<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $summary = $this->getCalendarSummary();
        $cells = $this->getCalendarCells();
        $listItems = $this->getListItems();
        $selectedItem = $this->getSelectedCalendarItem();
        $weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $monthOptions = $this->getMonthPickerOptions();
        $yearOptions = $this->getMonthPickerYearOptions();
    @endphp

    <style>
        .wm-calendar-page {
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

        .wm-calendar-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-calendar-stat,
        .wm-calendar-shell,
        .wm-calendar-form-card {
            padding: 1.1rem 1.2rem;
        }

        .wm-calendar-stat-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-calendar-stat-value {
            margin: 0.55rem 0 0;
            color: #2d2a26;
            font-size: 2rem;
            font-weight: 700;
        }

        .wm-calendar-stat-meta {
            margin: 0.55rem 0 0;
            color: #746d66;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .wm-calendar-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(20rem, 0.75fr);
            gap: 1rem;
            align-items: start;
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

        .wm-calendar-month-label:hover {
            border-color: #c9a96a;
            color: #8f6d29;
        }

        .wm-calendar-month-label-caret {
            width: 0.9rem;
            height: 0.9rem;
            color: #b89452;
        }

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

        .wm-calendar-legend-dot {
            width: 0.72rem;
            height: 0.72rem;
            border-radius: 999px;
            display: inline-block;
        }

        .wm-calendar-month-popup {
            position: absolute;
            top: calc(100% + 0.85rem);
            left: 50%;
            z-index: 20;
            width: min(32rem, calc(100vw - 2rem));
            transform: translateX(-50%);
            padding: 1.2rem;
            border: 1px solid #d8ccbd;
            border-radius: 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 20px 50px rgba(32, 24, 18, 0.16);
        }

        .wm-calendar-month-popup::before {
            content: "";
            position: absolute;
            top: -0.55rem;
            left: 50%;
            width: 1rem;
            height: 1rem;
            transform: translateX(-50%) rotate(45deg);
            background: rgba(255, 255, 255, 0.98);
            border-top: 1px solid #d8ccbd;
            border-left: 1px solid #d8ccbd;
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
            border-radius: 0.75rem;
            background: #fff;
            padding: 0 0.95rem;
            color: #4f4943;
            font-size: 1rem;
        }

        .wm-calendar-month-go {
            min-height: 3.2rem;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 0.35rem;
            background: #b89452;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-calendar-month-copy {
            margin: 1.2rem 0 1rem;
            color: #6c645d;
            font-size: 0.9rem;
            line-height: 1.6;
            text-align: center;
        }

        .wm-calendar-month-copy strong {
            font-family: 'Cinzel', serif;
            font-size: 1.05rem;
            color: #5a4e40;
        }

        .wm-calendar-month-actions {
            display: grid;
            gap: 0.8rem;
        }

        .wm-calendar-month-action {
            min-height: 3.15rem;
            border: 0;
            border-radius: 0.35rem;
            background: #b89452;
            color: #fff;
            font-size: 0.98rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-dot-olive { background: #87985e; }
        .wm-dot-sky { background: #5d8fb7; }
        .wm-dot-rose { background: #c57f88; }

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
            min-height: 10.5rem;
            border-radius: 1.05rem;
            border: 1px solid #ece5dd;
            background: #fffdfa;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            cursor: pointer;
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

        .wm-calendar-day.is-outside .wm-calendar-day-number {
            color: #b2aaa2;
        }

        .wm-calendar-items {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            min-height: 0;
            overflow: visible;
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

        .wm-calendar-item-title {
            display: block;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wm-calendar-item-subtitle {
            display: block;
            opacity: 0.88;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wm-calendar-item-time {
            display: block;
            opacity: 0.88;
            font-size: 0.72rem;
            margin-top: 0.08rem;
        }

        .wm-calendar-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .wm-calendar-list-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 0.85rem;
            align-items: start;
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            border: 1px solid #ece5dd;
            background: #fffdfa;
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

        .wm-calendar-list-title {
            margin: 0;
            color: #2d2a26;
            font-size: 0.96rem;
            font-weight: 700;
        }

        .wm-calendar-list-meta,
        .wm-calendar-list-text {
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
            width: min(34rem, calc(100vw - 2rem));
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

        .wm-calendar-detail-title {
            margin: 0;
            color: #2d2a26;
            font-size: 1.08rem;
            font-weight: 700;
        }

        .wm-calendar-detail-meta,
        .wm-calendar-detail-text {
            margin: 0.45rem 0 0;
            color: #746d66;
            font-size: 0.88rem;
            line-height: 1.6;
        }

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

        .wm-calendar-form-card {
            display: grid;
            gap: 0.95rem;
        }

        .wm-calendar-form-title {
            margin: 0;
            color: #2d2a26;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .wm-calendar-form-copy {
            margin: 0.35rem 0 0;
            color: #746d66;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .wm-calendar-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
        }

        .wm-calendar-field-grid.is-single {
            grid-template-columns: minmax(0, 1fr);
        }

        .wm-calendar-field label,
        .wm-calendar-toggle label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-calendar-input,
        .wm-calendar-textarea {
            width: 100%;
            min-height: 2.8rem;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0.75rem 0.95rem;
            color: #2d2a26;
        }

        .wm-calendar-textarea {
            min-height: 6rem;
            resize: vertical;
        }

        .wm-calendar-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            color: #4d473f;
            font-weight: 600;
        }

        .wm-calendar-empty {
            padding: 1rem 1.1rem;
            color: #746d66;
            font-size: 0.9rem;
        }

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

        @media (max-width: 1180px) {
            .wm-calendar-summary,
            .wm-calendar-layout {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 960px) {
            .wm-calendar-grid {
                gap: 0.5rem;
            }

            .wm-calendar-day {
                min-height: 8.8rem;
                padding: 0.6rem;
            }

            .wm-event-top-head {
                grid-template-columns: minmax(0, 1fr);
            }

            .wm-event-top-side {
                flex-direction: column;
                align-items: stretch;
            }

            .wm-event-summary-chip,
            .wm-event-countdown {
                width: 100%;
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

            .wm-calendar-field-grid,
            .wm-event-date-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .wm-calendar-list-item {
                grid-template-columns: auto minmax(0, 1fr);
            }

            .wm-calendar-list-side {
                grid-column: 2;
            }
        }
    </style>

    <div class="wm-calendar-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'calendar',
        ])

        <section class="wm-calendar-summary">
            <article class="wm-event-card wm-calendar-stat">
                <p class="wm-calendar-stat-label">Checklist deadlines</p>
                <p class="wm-calendar-stat-value">{{ $summary['checklist'] }}</p>
                <p class="wm-calendar-stat-meta">Task due dates pulled from the project checklist.</p>
            </article>
            <article class="wm-event-card wm-calendar-stat">
                <p class="wm-calendar-stat-label">Payment deadlines</p>
                <p class="wm-calendar-stat-value">{{ $summary['payments'] }}</p>
                <p class="wm-calendar-stat-meta">Scheduled due dates from supplier payments.</p>
            </article>
            <article class="wm-event-card wm-calendar-stat">
                <p class="wm-calendar-stat-label">Project events</p>
                <p class="wm-calendar-stat-value">{{ $summary['events'] }}</p>
                <p class="wm-calendar-stat-meta">Custom event moments created directly in the calendar.</p>
            </article>
            <article class="wm-event-card wm-calendar-stat">
                <p class="wm-calendar-stat-label">Total milestones</p>
                <p class="wm-calendar-stat-value">{{ $summary['total'] }}</p>
                <p class="wm-calendar-stat-meta">All dated items before or on the event window.</p>
            </article>
        </section>

        <section class="wm-calendar-layout">
            <article class="wm-event-card wm-calendar-shell">
                <div class="wm-calendar-toolbar">
                    <div class="wm-calendar-nav">
                        <button type="button" class="wm-calendar-nav-button" wire:click="previousMonth">Prev</button>
                        <div class="wm-calendar-month-picker" x-data="{ openMonthPicker: false }">
                            <button
                                type="button"
                                class="wm-calendar-month-label"
                                x-on:click="openMonthPicker = ! openMonthPicker"
                            >
                                <span>{{ $this->getMonthLabel() }}</span>
                                <x-heroicon-o-chevron-down class="wm-calendar-month-label-caret" />
                            </button>

                            <div
                                class="wm-calendar-month-popup"
                                x-cloak
                                x-show="openMonthPicker"
                                x-transition.opacity
                                x-on:click.outside="openMonthPicker = false"
                            >
                                <div class="wm-calendar-month-popup-grid">
                                    <select class="wm-calendar-month-select" wire:model="monthPickerForm.month">
                                        @foreach ($monthOptions as $monthNumber => $monthLabel)
                                            <option value="{{ $monthNumber }}">{{ $monthLabel }}</option>
                                        @endforeach
                                    </select>

                                    <input type="number" min="{{ min($yearOptions) }}" max="{{ max($yearOptions) }}" class="wm-calendar-month-year" wire:model="monthPickerForm.year">

                                    <button type="button" class="wm-calendar-month-go" wire:click="goToSelectedMonth" x-on:click="openMonthPicker = false">Go</button>
                                </div>

                                <p class="wm-calendar-month-copy">
                                    <strong>Project Event Date:</strong>
                                    {{ $record->event_start_date ? $record->event_start_date->format('F j, Y') : 'Date to be defined' }}
                                </p>

                                <div class="wm-calendar-month-actions">
                                    @if ($record->event_start_date)
                                        <button type="button" class="wm-calendar-month-action" wire:click="goToEventDate" x-on:click="openMonthPicker = false">
                                            Go to Project Event Date
                                        </button>
                                    @endif

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
                            <div class="wm-calendar-day {{ $cell['is_current_month'] ? '' : 'is-outside' }}" wire:click="selectCalendarDay('{{ $cell['date_key'] }}')" x-data="{ expanded: false }">
                                <div class="wm-calendar-day-head">
                                    <span class="wm-calendar-day-number">{{ $cell['date']->format('j') }}</span>
                                </div>

                                <div class="wm-calendar-items">
                                    @forelse ($cell['items']->take(4) as $item)
                                        <button
                                            type="button"
                                            class="wm-calendar-item is-{{ $item['color'] }} {{ $item['completed'] ? 'is-dimmed' : '' }}"
                                            wire:click.stop="openCalendarItem('{{ $item['kind'] }}', {{ $item['id'] }})"
                                            title="{{ $item['title'] }}{{ filled($item['subtitle']) ? ' - ' . $item['subtitle'] : '' }}"
                                        >
                                            @if ($item['kind'] === 'checklist')
                                                <input
                                                    type="checkbox"
                                                    class="wm-calendar-item-check"
                                                    @checked($item['completed'])
                                                    x-on:click.stop
                                                    x-on:change="$wire.toggleChecklistCompleted({{ $item['id'] }}, $event.target.checked)"
                                                >
                                            @else
                                                <span></span>
                                            @endif

                                            <div>
                                                <span class="wm-calendar-item-title">{{ $item['title'] }}</span>
                                                @if (filled($item['subtitle']))
                                                    <span class="wm-calendar-item-subtitle">{{ $item['subtitle'] }}</span>
                                                @endif
                                                @if ($item['kind'] === 'event' && ! $item['is_all_day'])
                                                    <span class="wm-calendar-item-time">{{ $item['starts_at']->format('H:i') }}{{ $item['ends_at'] ? ' - ' . $item['ends_at']->format('H:i') : '' }}</span>
                                                @endif
                                            </div>
                                        </button>
                                    @empty
                                    @endforelse

                                    @if ($cell['items']->count() > 4)
                                        <button
                                            type="button"
                                            class="wm-calendar-more"
                                            x-show="! expanded"
                                            x-on:click.stop="expanded = true"
                                        >
                                            + {{ $cell['items']->count() - 4 }} more events
                                        </button>

                                        <div x-cloak x-show="expanded" class="wm-calendar-items">
                                            @foreach ($cell['items']->slice(4) as $item)
                                                <button
                                                    type="button"
                                                    class="wm-calendar-item is-{{ $item['color'] }} {{ $item['completed'] ? 'is-dimmed' : '' }}"
                                                    wire:click.stop="openCalendarItem('{{ $item['kind'] }}', {{ $item['id'] }})"
                                                    title="{{ $item['title'] }}{{ filled($item['subtitle']) ? ' - ' . $item['subtitle'] : '' }}"
                                                >
                                                    @if ($item['kind'] === 'checklist')
                                                        <input
                                                            type="checkbox"
                                                            class="wm-calendar-item-check"
                                                            @checked($item['completed'])
                                                            x-on:click.stop
                                                            x-on:change="$wire.toggleChecklistCompleted({{ $item['id'] }}, $event.target.checked)"
                                                        >
                                                    @else
                                                        <span></span>
                                                    @endif

                                                    <div>
                                                        <span class="wm-calendar-item-title">{{ $item['title'] }}</span>
                                                        @if (filled($item['subtitle']))
                                                            <span class="wm-calendar-item-subtitle">{{ $item['subtitle'] }}</span>
                                                        @endif
                                                        @if ($item['kind'] === 'event' && ! $item['is_all_day'])
                                                            <span class="wm-calendar-item-time">{{ $item['starts_at']->format('H:i') }}{{ $item['ends_at'] ? ' - ' . $item['ends_at']->format('H:i') : '' }}</span>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
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
                                    <p class="wm-calendar-list-title">
                                        @if ($item['kind'] === 'checklist')
                                            <input
                                                type="checkbox"
                                                @checked($item['completed'])
                                                x-on:change="$wire.toggleChecklistCompleted({{ $item['id'] }}, $event.target.checked)"
                                            >
                                        @endif
                                        {{ $item['title'] }}
                                    </p>

                                    <p class="wm-calendar-list-meta">
                                        {{ $item['start_date']->format('M j, Y') }}
                                        @if ($item['kind'] === 'event' && $item['end_date']->ne($item['start_date']))
                                            - {{ $item['end_date']->format('M j, Y') }}
                                        @endif
                                        @if ($item['kind'] === 'event' && ! $item['is_all_day'])
                                            · {{ $item['starts_at']->format('H:i') }}{{ $item['ends_at'] ? ' - ' . $item['ends_at']->format('H:i') : '' }}
                                        @endif
                                    </p>

                                    @if (filled($item['subtitle']))
                                        <p class="wm-calendar-list-text">{{ $item['subtitle'] }}</p>
                                    @endif

                                    @if (filled($item['program_html']))
                                        <div class="wm-calendar-list-program">{!! $item['program_html'] !!}</div>
                                    @endif
                                </div>

                                <div class="wm-calendar-list-side">
                                    {{ match($item['kind']) {
                                        'checklist' => $item['completed'] ? 'Completed' : 'Checklist',
                                        'payment' => $item['payment_status'] === \App\Models\Payment::STATUS_PAID ? 'Paid' : 'Payment',
                                        default => $item['is_all_day'] ? 'All day' : 'Event',
                                    } }}
                                </div>
                            </article>
                        @empty
                            <div class="wm-calendar-empty">No dated items available for this event yet.</div>
                        @endforelse
                    </div>
                @endif
            </article>

            <aside class="wm-event-card wm-calendar-form-card">
                <div>
                    <h3 class="wm-calendar-form-title">Add project event</h3>
                    <p class="wm-calendar-form-copy">Create a dated event directly from the calendar. Use this for meetings, travel, inspections or a detailed event-day program.</p>
                </div>

                <div class="wm-calendar-field">
                    <label for="calendar-event-title">Title</label>
                    <input id="calendar-event-title" type="text" class="wm-calendar-input" wire:model="eventForm.title">
                </div>

                <div class="wm-calendar-field">
                    <label for="calendar-event-description">Description</label>
                    <textarea id="calendar-event-description" class="wm-calendar-textarea" rows="3" wire:model="eventForm.description"></textarea>
                </div>

                <label class="wm-calendar-toggle">
                    <input type="checkbox" wire:model.live="eventForm.is_multi_day">
                    <span>Event spans multiple days</span>
                </label>

                <label class="wm-calendar-toggle">
                    <input type="checkbox" wire:model.live="eventForm.is_all_day">
                    <span>All day</span>
                </label>

                @if (! ($eventForm['is_multi_day'] ?? false))
                    <div class="wm-calendar-field-grid is-single">
                        <div class="wm-calendar-field">
                            <label for="calendar-start-date">Date</label>
                            <input id="calendar-start-date" type="date" class="wm-calendar-input" wire:model="eventForm.start_date">
                        </div>
                    </div>
                @else
                    <div class="wm-calendar-field-grid">
                        <div class="wm-calendar-field">
                            <label for="calendar-start-date">Start date</label>
                            <input id="calendar-start-date" type="date" class="wm-calendar-input" wire:model="eventForm.start_date">
                        </div>
                        <div class="wm-calendar-field">
                            <label for="calendar-end-date">End date</label>
                            <input id="calendar-end-date" type="date" class="wm-calendar-input" wire:model="eventForm.end_date">
                        </div>
                    </div>
                @endif

                @if (! ($eventForm['is_all_day'] ?? false))
                    <div class="wm-calendar-field-grid">
                        <div class="wm-calendar-field">
                            <label for="calendar-start-time">Start time</label>
                            <input id="calendar-start-time" type="time" class="wm-calendar-input" wire:model="eventForm.start_time">
                        </div>
                        <div class="wm-calendar-field">
                            <label for="calendar-end-time">End time</label>
                            <input id="calendar-end-time" type="time" class="wm-calendar-input" wire:model="eventForm.end_time">
                        </div>
                    </div>
                @endif

                <label class="wm-calendar-toggle">
                    <input type="checkbox" wire:model.live="eventForm.include_program">
                    <span>Insert program</span>
                </label>

                @if ($eventForm['include_program'] ?? false)
                    <div class="wm-calendar-field">
                        <label for="calendar-program-html">Program HTML</label>
                        <textarea id="calendar-program-html" class="wm-calendar-textarea" rows="8" wire:model="eventForm.program_html"></textarea>
                    </div>
                @endif

                <div class="wm-event-date-actions">
                    <x-filament::button wire:click="saveProjectEvent">
                        Save event
                    </x-filament::button>
                </div>
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
