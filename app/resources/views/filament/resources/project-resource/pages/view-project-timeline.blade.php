<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $days = $this->getTimelineDays();
        $supplierOptions = $this->getSupplierOptions();
    @endphp

    <style>
        .wm-timeline-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-timeline-shell {
            width: min(1100px, calc(100% - 2rem));
            margin: 0 auto;
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

        .wm-timeline-stream {
            padding: 1.15rem 1.2rem;
        }

        .wm-timeline-stream {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            padding: 1.4rem 1.6rem;
        }

        .wm-timeline-day {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid #eee6dd;
        }

        .wm-timeline-day:last-child {
            border-bottom: 0;
        }

        .wm-timeline-day-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-timeline-toolbar {
            display: flex;
            justify-content: flex-end;
        }

        .wm-timeline-export {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 2.8rem;
            padding: 0 1rem;
            border: 1px solid #dfd0bf;
            border-radius: 999px;
            background: linear-gradient(180deg, #fffdfa 0%, #f9f3eb 100%);
            color: #6f5830;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(45, 42, 38, 0.06);
        }

        .wm-timeline-export svg {
            width: 1rem;
            height: 1rem;
        }

        .wm-timeline-day-title {
            margin: 0;
            color: #111;
            font-size: clamp(1.15rem, 2vw, 1.55rem);
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-timeline-day-meta {
            display: inline-flex;
            gap: 0.55rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .wm-timeline-chip {
            display: inline-flex;
            align-items: center;
            min-height: 1.9rem;
            padding: 0 0.72rem;
            border-radius: 999px;
            background: #f6f1e8;
            color: #605951;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .wm-timeline-add {
            border: 0;
            background: transparent;
            color: #b38b43;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
        }

        .wm-timeline-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-timeline-empty {
            padding: 0.5rem 0;
            color: #a39c95;
            font-style: italic;
        }

        .wm-timeline-item {
            display: grid;
            grid-template-columns: 9rem 1.6rem minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .wm-timeline-time {
            text-align: right;
            padding-top: 0.25rem;
        }

        .wm-timeline-time-value {
            margin: 0;
            color: #2d2a26;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .wm-timeline-time-range {
            margin: 0.25rem 0 0;
            color: #7a736c;
            font-size: 0.82rem;
        }

        .wm-timeline-marker {
            position: relative;
            min-height: 100%;
            display: flex;
            justify-content: center;
        }

        .wm-timeline-marker::before {
            content: "";
            position: absolute;
            top: 0.25rem;
            bottom: -1.25rem;
            width: 2px;
            background: linear-gradient(180deg, rgba(201, 169, 106, 0.28), rgba(201, 169, 106, 0.02));
        }

        .wm-timeline-marker-dot {
            position: relative;
            z-index: 1;
            width: 1rem;
            height: 1rem;
            margin-top: 0.45rem;
            border-radius: 999px;
            background: radial-gradient(circle at 30% 30%, #fff 0, #fff 18%, #d0b17c 20%, #b89452 100%);
            box-shadow: 0 0 0 0.35rem rgba(184, 148, 82, 0.12);
        }

        .wm-timeline-item-card {
            padding: 1rem 1.05rem;
            border-radius: 1.1rem;
            background: linear-gradient(180deg, #fffdfa 0%, #fbf8f4 100%);
            border: 1px solid #ece4da;
        }

        .wm-timeline-item-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: start;
        }

        .wm-timeline-item-title {
            margin: 0;
            color: #2d2a26;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .wm-timeline-item-actions {
            display: inline-flex;
            gap: 0.4rem;
        }

        .wm-timeline-action {
            border: 0;
            background: #f6f1e8;
            color: #6a6158;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            cursor: pointer;
        }

        .wm-timeline-action.is-danger {
            color: #a96f66;
        }

        .wm-timeline-item-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.7rem;
        }

        .wm-timeline-item-text {
            margin: 0.8rem 0 0;
            color: #5f5953;
            font-size: 0.9rem;
            line-height: 1.7;
        }

        .wm-timeline-images {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(6.75rem, 8.25rem));
            gap: 0.7rem;
            margin-top: 0.85rem;
            justify-content: start;
        }

        .wm-timeline-image {
            border-radius: 0.95rem;
            overflow: hidden;
            background: #f3efe8;
            width: 100%;
            max-width: 8.25rem;
            aspect-ratio: 1 / 1;
        }

        .wm-timeline-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .wm-timeline-editor-title {
            margin: 0;
            color: #2d2a26;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .wm-timeline-editor-copy {
            margin: 0.35rem 0 0;
            color: #746d66;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .wm-timeline-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
        }

        .wm-timeline-field-grid.is-three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .wm-timeline-field label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-timeline-input,
        .wm-timeline-textarea,
        .wm-timeline-select {
            width: 100%;
            min-height: 2.8rem;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0.75rem 0.95rem;
            color: #2d2a26;
        }

        .wm-timeline-textarea {
            min-height: 6rem;
            resize: vertical;
        }

        .wm-timeline-upload {
            display: grid;
            gap: 0.6rem;
        }

        .wm-timeline-upload-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(6rem, 1fr));
            gap: 0.65rem;
        }

        .wm-timeline-upload-thumb {
            position: relative;
            border-radius: 0.9rem;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            background: #f3efe8;
        }

        .wm-timeline-upload-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .wm-timeline-upload-remove {
            position: absolute;
            top: 0.45rem;
            right: 0.45rem;
            border: 0;
            width: 1.9rem;
            height: 1.9rem;
            border-radius: 999px;
            background: rgba(24, 18, 14, 0.72);
            color: #fff;
            cursor: pointer;
        }

        .wm-timeline-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(31, 25, 20, 0.34);
        }

        .wm-timeline-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 50;
            width: min(52rem, calc(100vw - 2rem));
            max-height: calc(100vh - 2rem);
            overflow: auto;
            transform: translate(-50%, -50%);
            border-radius: 1.25rem;
            border: 1px solid #d9ccc0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 24px 60px rgba(24, 18, 14, 0.18);
            padding: 1.35rem;
        }

        .wm-timeline-modal.is-compact {
            width: min(30rem, calc(100vw - 2rem));
        }

        .wm-timeline-modal-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .wm-timeline-modal-close {
            border: 0;
            width: 2.3rem;
            height: 2.3rem;
            border-radius: 999px;
            background: #f4eee6;
            color: #6a6158;
            cursor: pointer;
            flex: 0 0 auto;
        }

        .wm-timeline-modal-copy {
            margin: 0;
            color: #5f5953;
            font-size: 0.98rem;
            line-height: 1.6;
        }

        .wm-timeline-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.8rem;
            margin-top: 1.1rem;
        }

        .wm-timeline-empty-state {
            padding: 1.2rem;
            color: #857d76;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .wm-timeline-shell {
                width: min(100%, calc(100% - 1rem));
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

            .wm-timeline-item {
                grid-template-columns: minmax(0, 1fr);
                gap: 0.55rem;
            }

            .wm-timeline-time {
                text-align: left;
            }

            .wm-timeline-marker {
                display: none;
            }

            .wm-timeline-field-grid,
            .wm-timeline-field-grid.is-three,
            .wm-event-date-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="wm-timeline-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'timeline',
        ])

        <div class="wm-timeline-shell">
            <div class="wm-timeline-toolbar">
                <button type="button" class="wm-timeline-export" wire:click="exportTimelinePdf">
                    <x-heroicon-o-document-arrow-down />
                    <span>Esporta PDF</span>
                </button>
            </div>

            <article class="wm-event-card wm-timeline-stream">
                @if ($days->isEmpty())
                    <div class="wm-timeline-empty-state">
                        The project does not have start and end dates yet. Define the project dates first to unlock the event timeline.
                    </div>
                @else
                    @foreach ($days as $day)
                        <section class="wm-timeline-day">
                            <div class="wm-timeline-day-head">
                                <div>
                                    <h3 class="wm-timeline-day-title">{{ $day['date']->format('l, F j, Y') }}</h3>
                                    <div class="wm-timeline-day-meta">
                                        @if ($day['sunset_time'])
                                            <span class="wm-timeline-chip">Sunset {{ $day['sunset_time']->format('H:i') }}</span>
                                        @endif
                                        <span class="wm-timeline-chip">{{ $day['items']->count() }} items</span>
                                    </div>
                                </div>

                                <button type="button" class="wm-timeline-add" wire:click="startCreateTimelineItem('{{ $day['key'] }}')">
                                    + Add item
                                </button>
                            </div>

                            @if ($day['items']->isEmpty())
                                <div class="wm-timeline-empty">No timeline items for this day yet.</div>
                            @else
                                <div class="wm-timeline-items">
                                    @foreach ($day['items'] as $item)
                                        <article class="wm-timeline-item">
                                            <div class="wm-timeline-time">
                                                <p class="wm-timeline-time-value">{{ $item->start_time ? $item->start_time->format('H:i') : '—' }}</p>
                                                @if ($item->end_time)
                                                    <p class="wm-timeline-time-range">to {{ $item->end_time->format('H:i') }}</p>
                                                @endif
                                            </div>

                                            <div class="wm-timeline-marker">
                                                <span class="wm-timeline-marker-dot"></span>
                                            </div>

                                            <div class="wm-timeline-item-card">
                                                <div class="wm-timeline-item-head">
                                                    <h4 class="wm-timeline-item-title">{{ $item->title }}</h4>

                                                    <div class="wm-timeline-item-actions">
                                                        <button type="button" class="wm-timeline-action" wire:click="editTimelineItem({{ $item->id }})">
                                                            <x-heroicon-o-pencil-square />
                                                        </button>
                                                        <button type="button" class="wm-timeline-action is-danger" wire:click="promptDeleteTimelineItem({{ $item->id }})">
                                                            <x-heroicon-o-trash />
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="wm-timeline-item-grid">
                                                    @if ($item->location)
                                                        <span class="wm-timeline-chip">{{ $item->location }}</span>
                                                    @endif
                                                    @if ($item->supplier?->name)
                                                        <span class="wm-timeline-chip">{{ $item->supplier->name }}</span>
                                                    @endif
                                                    @if ($item->sunset_time)
                                                        <span class="wm-timeline-chip">Sunset {{ $item->sunset_time->format('H:i') }}</span>
                                                    @endif
                                                </div>

                                                @if ($item->description)
                                                    <p class="wm-timeline-item-text">{{ $item->description }}</p>
                                                @endif

                                                @if ($item->notes)
                                                    <p class="wm-timeline-item-text"><strong>Notes:</strong> {{ $item->notes }}</p>
                                                @endif

                                                @if (! empty($item->image_paths))
                                                    <div class="wm-timeline-images">
                                                        @foreach ($item->image_paths as $path)
                                                            <div class="wm-timeline-image">
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="Timeline image">
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endforeach
                @endif
            </article>
        </div>

        @if ($showTimelineEditor)
            <div class="wm-timeline-modal-backdrop" wire:click="closeTimelineEditor"></div>
            <div class="wm-timeline-modal" role="dialog" aria-modal="true">
                <div class="wm-timeline-modal-head">
                    <div>
                        <h3 class="wm-timeline-editor-title">{{ $editingTimelineItemId ? 'Edit timeline item' : 'Add timeline item' }}</h3>
                        <p class="wm-timeline-editor-copy">Use the project event dates as the frame, then define timings, sunset reference, location, supplier, notes and visual attachments for each operational phase.</p>
                    </div>

                    <button type="button" class="wm-timeline-modal-close" wire:click="closeTimelineEditor">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>

                <div class="wm-timeline-field-grid is-three">
                    <div class="wm-timeline-field">
                        <label for="timeline-date">Date</label>
                        <input id="timeline-date" type="date" class="wm-timeline-input" wire:model="timelineForm.timeline_date">
                    </div>
                    <div class="wm-timeline-field">
                        <label for="timeline-start-time">Start</label>
                        <input id="timeline-start-time" type="time" class="wm-timeline-input" wire:model="timelineForm.start_time">
                    </div>
                    <div class="wm-timeline-field">
                        <label for="timeline-end-time">End</label>
                        <input id="timeline-end-time" type="time" class="wm-timeline-input" wire:model="timelineForm.end_time">
                    </div>
                </div>

                <div class="wm-timeline-field-grid">
                    <div class="wm-timeline-field">
                        <label for="timeline-sunset-time">Sunset</label>
                        <input id="timeline-sunset-time" type="time" class="wm-timeline-input" wire:model="timelineForm.sunset_time">
                    </div>
                    <div class="wm-timeline-field">
                        <label for="timeline-supplier">Supplier</label>
                        <select id="timeline-supplier" class="wm-timeline-select" wire:model="timelineForm.supplier_id">
                            <option value="">None</option>
                            @foreach ($supplierOptions as $supplierId => $supplierName)
                                <option value="{{ $supplierId }}">{{ $supplierName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="wm-timeline-field">
                    <label for="timeline-location">Location</label>
                    <input id="timeline-location" type="text" class="wm-timeline-input" wire:model="timelineForm.location">
                </div>

                <div class="wm-timeline-field">
                    <label for="timeline-title">Activity title</label>
                    <input id="timeline-title" type="text" class="wm-timeline-input" wire:model="timelineForm.title">
                </div>

                <div class="wm-timeline-field">
                    <label for="timeline-description">Activity description</label>
                    <textarea id="timeline-description" class="wm-timeline-textarea" rows="4" wire:model="timelineForm.description"></textarea>
                </div>

                <div class="wm-timeline-field">
                    <label for="timeline-notes">Notes</label>
                    <textarea id="timeline-notes" class="wm-timeline-textarea" rows="4" wire:model="timelineForm.notes"></textarea>
                </div>

                <div class="wm-timeline-field wm-timeline-upload">
                    <label for="timeline-images">Images</label>
                    <input id="timeline-images" type="file" multiple wire:model="timelineImageUploads">

                    @if (! empty($timelineForm['existing_image_paths']))
                        <div class="wm-timeline-upload-list">
                            @foreach ($timelineForm['existing_image_paths'] as $index => $path)
                                <div class="wm-timeline-upload-thumb">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="Timeline image">
                                    <button type="button" class="wm-timeline-upload-remove" wire:click="removeTimelineImage({{ $index }})">
                                        <x-heroicon-o-x-mark />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($timelineImageUploads))
                        <div class="wm-timeline-upload-list">
                            @foreach ($timelineImageUploads as $upload)
                                <div class="wm-timeline-upload-thumb">
                                    <img src="{{ $upload->temporaryUrl() }}" alt="Timeline upload preview">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="wm-timeline-modal-actions">
                    <x-filament::button color="gray" wire:click="closeTimelineEditor">
                        Cancel
                    </x-filament::button>
                    <x-filament::button wire:click="saveTimelineItem">
                        {{ $editingTimelineItemId ? 'Update item' : 'Save item' }}
                    </x-filament::button>
                </div>
            </div>
        @endif

        @if ($confirmDeleteTimelineItemId)
            <div class="wm-timeline-modal-backdrop" wire:click="cancelDeleteTimelineItem"></div>
            <div class="wm-timeline-modal is-compact" role="dialog" aria-modal="true">
                <p class="wm-timeline-modal-copy">Deleting this timeline item will permanently remove its content and images from the project timeline.</p>

                <div class="wm-timeline-modal-actions">
                    <x-filament::button color="gray" wire:click="cancelDeleteTimelineItem">
                        Cancel
                    </x-filament::button>
                    <x-filament::button color="danger" wire:click="confirmDeleteTimelineItem">
                        Delete item
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
