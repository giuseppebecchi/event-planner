<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $summary = $this->getChecklistSummary();
        $sections = $this->getChecklistSections();
        $supplierOptions = $this->getSupplierOptions();
        $isCustomer = auth()->user()?->isCustomer();
    @endphp

    <style>
        .wm-checklist-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        [x-cloak] {
            display: none !important;
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

        .wm-checklist-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-checklist-stat {
            padding: 1.15rem 1.2rem;
        }

        .wm-checklist-stat-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-checklist-stat-value {
            margin: 0.55rem 0 0;
            color: #2d2a26;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .wm-checklist-stat-meta {
            margin: 0.55rem 0 0;
            color: #746d66;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .wm-checklist-board {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
            align-items: start;
        }

        .wm-checklist-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.7rem;
        }

        .wm-checklist-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.8rem 1rem;
            border-radius: 999px;
            border: 1px solid #e7dfd5;
            background: rgba(255, 255, 255, 0.92);
            color: #5f5953;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .wm-checklist-section {
            padding: 1.25rem 1.35rem;
        }

        .wm-checklist-section-head {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.15rem;
        }

        .wm-checklist-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4.2rem;
            height: 4.2rem;
            border-radius: 999px;
            border: 3px solid #d7d1ca;
            background: #f8f5f1;
            color: #bbb4ad;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 0.06em;
        }

        .wm-checklist-section-title {
            margin: 0;
            color: #111;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-checklist-section-subtitle {
            margin: 0.25rem 0 0;
            color: #6f6963;
            font-size: 0.95rem;
            font-style: italic;
        }

        .wm-checklist-items {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .wm-checklist-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 0.9rem;
            align-items: start;
            padding: 0.35rem 0;
            position: relative;
        }

        .wm-checklist-item.is-completed {
            opacity: 0.54;
        }

        .wm-checklist-item.is-expanded {
            z-index: 45;
        }

        .wm-checklist-toggle {
            margin-top: 0.45rem;
            width: 2rem;
            height: 2rem;
            border-radius: 0.45rem;
            border: 2px solid #d4cec5;
            background: #fffdf9;
            accent-color: #c8bf7a;
        }

        .wm-checklist-main {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 0;
        }

        .wm-checklist-summary-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            min-height: 2.6rem;
            width: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-align: left;
        }

        .wm-checklist-summary-title {
            color: #4f4943;
            font-size: 0.98rem;
            line-height: 1.45;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wm-checklist-summary-copy {
            display: grid;
            gap: 0.18rem;
            min-width: 0;
        }

        .wm-checklist-summary-details {
            color: #9a9289;
            font-size: 0.82rem;
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wm-checklist-item.is-completed .wm-checklist-summary-title {
            color: #9c948c;
        }

        .wm-checklist-item.is-completed .wm-checklist-summary-details {
            color: #bbb4ad;
        }

        .wm-checklist-title-input,
        .wm-checklist-details-input,
        .wm-checklist-response-input {
            width: 100%;
            border: 0;
            border-bottom: 1px solid transparent;
            background: transparent;
            color: #4f4943;
            padding: 0.1rem 0;
            outline: none;
        }

        .wm-checklist-title-input {
            font-size: 0.98rem;
            line-height: 1.5;
        }

        .wm-checklist-item.is-completed .wm-checklist-title-input,
        .wm-checklist-item.is-completed .wm-checklist-details-input,
        .wm-checklist-item.is-completed .wm-checklist-response-input {
            color: #9c948c;
        }

        .wm-checklist-title-input:focus,
        .wm-checklist-details-input:focus,
        .wm-checklist-response-input:focus {
            border-bottom-color: #c9a96a;
        }

        .wm-checklist-details-input,
        .wm-checklist-response-input {
            min-height: 3.4rem;
            resize: vertical;
            font-size: 0.9rem;
            line-height: 1.45;
            color: #877e75;
        }

        .wm-checklist-response-box {
            display: grid;
            gap: 0.35rem;
            padding: 0.65rem 0.75rem;
            border: 1px solid #ece5dd;
            border-radius: 0.85rem;
            background: #fffdf9;
        }

        .wm-checklist-response-label,
        .wm-checklist-fill-toggle {
            color: #7a7168;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .wm-checklist-fill-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
        }

        .wm-checklist-fill-toggle input {
            width: 1rem;
            height: 1rem;
            accent-color: #7a8f7b;
        }

        .wm-checklist-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .wm-checklist-editor {
            display: grid;
            gap: 0.55rem;
            padding-top: 0.5rem;
        }

        .wm-checklist-schedule {
            display: grid;
            gap: 0.65rem;
            padding-top: 0.15rem;
        }

        .wm-checklist-supplier-field {
            display: grid;
            gap: 0.35rem;
            max-width: 24rem;
        }

        .wm-checklist-supplier-label {
            color: #7a7168;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .wm-checklist-schedule-toggle {
            display: inline-flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .wm-checklist-schedule-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 2rem;
            padding: 0 0.85rem;
            border-radius: 999px;
            border: 1px solid #e2d8ca;
            background: #fbf8f4;
            color: #6c645d;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-checklist-schedule-chip.is-active {
            border-color: #c9a96a;
            background: #fffaf2;
            color: #8f6d29;
        }

        .wm-checklist-schedule-grid {
            display: grid;
            grid-template-columns: 6rem 9rem;
            gap: 0.65rem;
            align-items: center;
        }

        .wm-checklist-schedule-input,
        .wm-checklist-schedule-select,
        .wm-checklist-supplier-select {
            width: 100%;
            min-height: 2.55rem;
            border: 1px solid #ddd2c5;
            border-radius: 0.85rem;
            background: #fff;
            padding: 0 0.85rem;
            color: #4f4943;
        }

        .wm-checklist-schedule-date {
            width: min(14rem, 100%);
        }

        .wm-checklist-pill {
            display: inline-flex;
            align-items: center;
            min-height: 1.7rem;
            padding: 0 0.65rem;
            border-radius: 999px;
            background: #f6f1e8;
            color: #665f57;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .wm-checklist-side {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.55rem;
            min-width: 10.8rem;
            padding-top: 0.4rem;
        }

        .wm-checklist-time {
            color: #6f6963;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .wm-checklist-time.is-overdue {
            display: grid;
            justify-items: end;
            gap: 0.15rem;
            color: #b42318;
            font-weight: 800;
        }

        .wm-checklist-overdue-note {
            color: #b42318;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1.2;
            white-space: nowrap;
        }

        .wm-checklist-actions {
            display: inline-flex;
            gap: 0.3rem;
        }

        .wm-checklist-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 0;
            border-radius: 999px;
            background: #f6f1e8;
            color: #b38b43;
            cursor: pointer;
        }

        .wm-checklist-action.is-delete {
            color: #a16c63;
        }

        .wm-checklist-response-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.42rem;
            min-height: 2.15rem;
            max-width: 10.8rem;
            border: 1px solid rgba(199, 62, 62, 0.36);
            border-radius: 999px;
            background: rgba(199, 62, 62, 0.1);
            color: #a33a3a;
            padding: 0 0.68rem;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
            white-space: nowrap;
        }

        .wm-checklist-response-trigger svg {
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
        }

        .wm-checklist-response-trigger.is-filled {
            border-color: rgba(46, 74, 98, 0.24);
            background: rgba(46, 74, 98, 0.09);
            color: #2e4a62;
        }

        .wm-checklist-response-trigger:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(92, 70, 49, 0.08);
        }

        .wm-checklist-divider {
            height: 1px;
            background: #ece4da;
            margin: 0.1rem 0;
        }

        .wm-checklist-empty {
            color: #a29a92;
            font-size: 1rem;
            font-style: italic;
            padding: 0.3rem 0;
        }

        .wm-checklist-add-row {
            margin-bottom: 0.95rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #ece4da;
            display: flex;
            justify-content: flex-start;
        }

        .wm-checklist-add-button {
            border: 0;
            background: transparent;
            color: #b38b43;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
        }

        .wm-checklist-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(39, 32, 24, 0.18);
        }

        .wm-checklist-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 50;
            width: min(31rem, calc(100vw - 2rem));
            transform: translate(-50%, -50%);
            border: 2px solid #d93025;
            border-radius: 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 24px 60px rgba(24, 18, 14, 0.18);
            padding: 1.65rem;
        }

        .wm-checklist-modal-copy {
            margin: 0;
            color: #d93025;
            font-size: 1.1rem;
            line-height: 1.55;
            text-align: center;
        }

        .wm-checklist-modal-actions {
            display: flex;
            justify-content: center;
            gap: 0.9rem;
            margin-top: 1.35rem;
        }

        .wm-checklist-modal-button {
            min-width: 8.6rem;
            min-height: 3rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            color: #6f6963;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-checklist-modal-button.is-danger {
            border-color: #d93025;
            color: #d93025;
        }


        @media (max-width: 1100px) {
            .wm-checklist-summary,
            .wm-checklist-board {
                grid-template-columns: minmax(0, 1fr);
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

        @media (max-width: 720px) {
            .wm-checklist-item {
                grid-template-columns: auto minmax(0, 1fr);
            }

            .wm-checklist-side {
                grid-column: 2;
                align-items: flex-start;
                min-width: 0;
                padding-top: 0;
            }

            .wm-event-date-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="wm-checklist-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'checklist',
        ])

        <section class="wm-checklist-summary">
            @if (! $isCustomer)
                <article class="wm-event-card wm-checklist-stat">
                    <p class="wm-checklist-stat-label">Sections</p>
                    <p class="wm-checklist-stat-value">{{ $summary['sections'] }}</p>
                    <p class="wm-checklist-stat-meta">Admin, client and supplier task boards.</p>
                </article>
            @endif
            <article class="wm-event-card wm-checklist-stat">
                <p class="wm-checklist-stat-label">Total tasks</p>
                <p class="wm-checklist-stat-value">{{ $summary['total'] }}</p>
                <p class="wm-checklist-stat-meta">Enabled checklist activities in the project.</p>
            </article>
            <article class="wm-event-card wm-checklist-stat">
                <p class="wm-checklist-stat-label">Completed</p>
                <p class="wm-checklist-stat-value">{{ $summary['completed'] }}</p>
                <p class="wm-checklist-stat-meta">{{ $summary['open'] }} still open.</p>
            </article>
            <article class="wm-event-card wm-checklist-stat">
                <p class="wm-checklist-stat-label">Due soon</p>
                <p class="wm-checklist-stat-value">{{ $summary['due_soon'] }}</p>
                <p class="wm-checklist-stat-meta">Open tasks due in the next 30 days.</p>
            </article>
        </section>

        <div class="wm-checklist-toolbar">
            <label class="wm-checklist-filter">
                <input type="checkbox" wire:model.live="hideCompleted">
                <span>Hide completed</span>
            </label>
        </div>

        <section class="wm-checklist-board">
            @foreach ($sections as $section)
                <article class="wm-event-card wm-checklist-section">
                    <div class="wm-checklist-section-head">
                        <div class="wm-checklist-avatar">{{ $section['avatar'] }}</div>

                        <div>
                            <h3 class="wm-checklist-section-title">{{ $section['title'] }}</h3>
                            <p class="wm-checklist-section-subtitle">{{ $section['subtitle'] }}</p>
                        </div>
                    </div>

                    @if (! $isCustomer)
                        <div class="wm-checklist-add-row">
                            @if (str_starts_with($section['key'], 'supplier-'))
                                <button
                                    type="button"
                                    class="wm-checklist-add-button"
                                    wire:click="addChecklistItem('supplier', {{ $section['items']->first()?->supplier_id ? $section['items']->first()->supplier_id : 'null' }})"
                                >
                                    + Add task
                                </button>
                            @elseif ($section['key'] === 'client')
                                <button type="button" class="wm-checklist-add-button" wire:click="addChecklistItem('client')">
                                    + Add task
                                </button>
                            @else
                                <button type="button" class="wm-checklist-add-button" wire:click="addChecklistItem('admin')">
                                    + Add task
                                </button>
                            @endif
                        </div>
                    @endif

                    @if ($section['items']->isEmpty())
                        <div class="wm-checklist-empty">
                            {{ $hideCompleted && ($section['total_count'] ?? 0) > 0 ? 'All tasks in this section are completed.' : 'No tasks currently assigned.' }}
                        </div>
                    @else
                        <div class="wm-checklist-items">
                            @foreach ($section['items'] as $item)
                                @php
                                    $isExpanded = $expandedChecklistItemId === $item->id;
                                    $isOverdue = ! $item->completed
                                        && $item->due_date
                                        && $item->due_date->copy()->startOfDay()->lt(now()->startOfDay());
                                    $overdueDays = $isOverdue
                                        ? $item->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay())
                                        : 0;
                                    $timeLabel = $item->due_date
                                        ? $item->due_date->format('M j, Y')
                                        : ($item->anticipation ?: 'No timeframe');
                                    $titleLabel = trim(strip_tags((string) ($checklistForms[$item->id]['title'] ?? $item->title ?? '')));
                                    $detailsLabel = trim(strip_tags((string) ($checklistForms[$item->id]['details'] ?? $item->details ?? '')));
                                    $responseLabel = trim(strip_tags((string) ($checklistForms[$item->id]['response'] ?? $item->response ?? '')));
                                @endphp

                                <div class="wm-checklist-item {{ $item->completed ? 'is-completed' : '' }} {{ $isExpanded ? 'is-expanded' : '' }}" wire:key="checklist-item-{{ $item->id }}">
                                    <input
                                        type="checkbox"
                                        class="wm-checklist-toggle"
                                        @checked($item->completed)
                                        x-on:click.stop
                                        x-on:change="$wire.toggleChecklistCompleted({{ $item->id }}, $event.target.checked)"
                                    >

                                    <div
                                        class="wm-checklist-main"
                                        @if ($isExpanded)
                                            x-data="{ mode: @js($checklistForms[$item->id]['due_date_mode'] ?? 'relative') }"
                                            x-on:mousedown.window="if (! $el.contains($event.target) && ! $event.target.closest('[data-checklist-delete-modal]') && ! $event.target.closest('[data-checklist-response-control]')) { $wire.collapseChecklistItem() }"
                                        @endif
                                    >
                                        @if (! $isExpanded)
                                            <button
                                                type="button"
                                                class="wm-checklist-summary-row"
                                                wire:click="expandChecklistItem({{ $item->id }})"
                                            >
                                                <span class="wm-checklist-summary-copy">
                                                    <span class="wm-checklist-summary-title">{{ $titleLabel !== '' ? $titleLabel : '(Unnamed Task)' }}</span>
                                                    @if ($detailsLabel !== '')
                                                        <span class="wm-checklist-summary-details">{{ $detailsLabel }}</span>
                                                    @endif
                                                </span>
                                                <span class="wm-checklist-time {{ $isOverdue ? 'is-overdue' : '' }}">
                                                    <span>{{ $timeLabel }}</span>
                                                    @if ($isOverdue)
                                                        <span class="wm-checklist-overdue-note">
                                                            {{ $overdueDays }} {{ \Illuminate\Support\Str::plural('day', $overdueDays) }} overdue
                                                        </span>
                                                    @endif
                                                </span>
                                            </button>
                                        @else
                                            <div class="wm-checklist-editor">
                                                @if ($isCustomer)
                                                    <div class="wm-checklist-summary-title">{{ $titleLabel !== '' ? $titleLabel : '(Unnamed Task)' }}</div>
                                                @else
                                                    <input
                                                        type="text"
                                                        class="wm-checklist-title-input"
                                                        placeholder="Enter a task description"
                                                        wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.title"
                                                    >
                                                @endif

                                                @if ($isCustomer)
                                                    @if ($detailsLabel !== '')
                                                        <div class="wm-checklist-summary-details">{{ $detailsLabel }}</div>
                                                    @endif
                                                @else
                                                    <textarea
                                                        class="wm-checklist-details-input"
                                                        rows="3"
                                                        placeholder="details"
                                                        wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.details"
                                                    ></textarea>
                                                @endif

                                                @if (! $isCustomer)
                                                    <label class="wm-checklist-fill-toggle">
                                                        <input
                                                            type="checkbox"
                                                            wire:model.live="checklistForms.{{ $item->id }}.to_be_filled"
                                                        >
                                                        <span>Requires response</span>
                                                    </label>
                                                    <label class="wm-checklist-fill-toggle">
                                                        <input
                                                            type="checkbox"
                                                            wire:model.live="checklistForms.{{ $item->id }}.insert_into_recap"
                                                        >
                                                        <span>Insert into recap</span>
                                                    </label>
                                                @endif

                                                @if (! $isCustomer)
                                                    <label class="wm-checklist-supplier-field">
                                                        <span class="wm-checklist-supplier-label">Supplier</span>
                                                        <select
                                                            class="wm-checklist-supplier-select"
                                                            wire:model.live="checklistForms.{{ $item->id }}.supplier_id"
                                                        >
                                                            <option value="">No supplier</option>
                                                            @foreach ($supplierOptions as $supplierId => $supplierName)
                                                                <option value="{{ $supplierId }}">{{ $supplierName }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                @endif

                                                @if (! $isCustomer)
                                                <div class="wm-checklist-schedule">
                                                    <div class="wm-checklist-schedule-toggle">
                                                        <button
                                                            type="button"
                                                            class="wm-checklist-schedule-chip"
                                                            x-bind:class="{ 'is-active': mode === 'relative' }"
                                                            x-on:mousedown.stop.prevent="mode = 'relative'; $wire.set('checklistForms.{{ $item->id }}.due_date_mode', 'relative')"
                                                            x-on:click.stop.prevent
                                                        >
                                                            Relative
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="wm-checklist-schedule-chip"
                                                            x-bind:class="{ 'is-active': mode === 'exact' }"
                                                            x-on:mousedown.stop.prevent="mode = 'exact'; $wire.set('checklistForms.{{ $item->id }}.due_date_mode', 'exact')"
                                                            x-on:click.stop.prevent
                                                        >
                                                            Exact date
                                                        </button>
                                                    </div>

                                                    <div class="wm-checklist-schedule-date" x-cloak x-show="mode === 'exact'">
                                                        <input
                                                            type="date"
                                                            class="wm-checklist-schedule-input"
                                                            wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.exact_due_date"
                                                        >
                                                    </div>

                                                    <div class="wm-checklist-schedule-grid" x-cloak x-show="mode === 'relative'">
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            class="wm-checklist-schedule-input"
                                                            placeholder="3"
                                                            wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.anticipation_value"
                                                        >
                                                        <select
                                                            class="wm-checklist-schedule-select"
                                                            wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.anticipation_unit"
                                                        >
                                                            <option value="days">Days</option>
                                                            <option value="weeks">Weeks</option>
                                                            <option value="months">Months</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                @endif

                                                <div class="wm-checklist-meta">
                                                    <span class="wm-checklist-pill">{{ $item->checklist?->title ?? 'Checklist' }}</span>
                                                    @if ($item->supplier)
                                                        <span class="wm-checklist-pill">{{ $item->supplier->name }}</span>
                                                    @endif
                                                    @if ($item->completed_at)
                                                        <span class="wm-checklist-pill">Completed {{ $item->completed_at->format('d/m H:i') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="wm-checklist-side">
                                        @if ($item->to_be_filled || (bool) ($checklistForms[$item->id]['to_be_filled'] ?? false))
                                            <button
                                                type="button"
                                                data-checklist-response-control
                                                class="wm-checklist-response-trigger {{ $responseLabel !== '' ? 'is-filled' : '' }}"
                                                wire:click.stop="mountAction('editChecklistResponse', { item: {{ $item->id }} })"
                                                x-on:mousedown.stop
                                            >
                                                <x-heroicon-o-pencil-square />
                                                <span>{{ $responseLabel !== '' ? 'Edit Response' : 'Insert response' }}</span>
                                            </button>
                                        @endif

                                        @if ($isExpanded && ! $isCustomer)
                                            <div class="wm-checklist-actions">
                                                <button
                                                    type="button"
                                                    class="wm-checklist-action is-delete"
                                                    x-on:mousedown.stop
                                                    wire:click.stop="promptDeleteChecklistItem({{ $item->id }})"
                                                    title="Delete task"
                                                >
                                                    <x-heroicon-o-trash />
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if (! $loop->last)
                                    <div class="wm-checklist-divider"></div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </section>

        @if ($confirmDeleteChecklistItemId)
            <div class="wm-checklist-modal-backdrop" data-checklist-delete-modal wire:click="cancelDeleteChecklistItem"></div>
            <div
                class="wm-checklist-modal"
                data-checklist-delete-modal
                role="dialog"
                aria-modal="true"
                aria-labelledby="checklist-delete-title"
                x-on:mousedown.stop
                x-on:click.stop
            >
                <p id="checklist-delete-title" class="wm-checklist-modal-copy">
                    Deleting this item will permanently remove it from the project's checklist.
                </p>

                <div class="wm-checklist-modal-actions">
                    <button type="button" class="wm-checklist-modal-button" wire:click.stop="cancelDeleteChecklistItem">
                        Cancel
                    </button>
                    <button type="button" class="wm-checklist-modal-button is-danger" wire:click.stop="confirmDeleteChecklistItem">
                        Delete item
                    </button>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
