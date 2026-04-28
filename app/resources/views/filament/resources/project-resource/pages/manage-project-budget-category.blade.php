<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $budget = $this->categoryBudgetRecord;
        $summary = $this->getBudgetSummary();
        $requests = $this->getExistingRequests();
        $supplierResults = $this->getSupplierResults();
    @endphp

    <style>
        .wm-scout-page {
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

        .wm-event-countdown-label {
            margin: 0;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.64);
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

        .wm-scout-summary,
        .wm-scout-request-list,
        .wm-scout-search {
            padding: 1.2rem 1.25rem;
        }

        .wm-scout-topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-scout-backlink {
            color: #2e4a62;
            font-weight: 700;
            text-decoration: none;
        }

        .wm-scout-title {
            margin: 0.3rem 0 0;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.45rem, 2vw, 1.9rem);
            color: #2d2a26;
        }

        .wm-scout-note {
            margin: 0.5rem 0 0;
            color: #6d665f;
            line-height: 1.7;
            max-width: 44rem;
        }

        .wm-scout-status {
            display: inline-flex;
            align-items: center;
            min-height: 2.1rem;
            padding: 0 0.9rem;
            border-radius: 999px;
            background: rgba(104, 112, 123, 0.12);
            color: #5f6670;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-scout-status.is-confirmed {
            background: rgba(83, 168, 106, 0.14);
            color: #2d7a39;
        }

        .wm-scout-status.is-evaluation {
            background: rgba(216, 177, 79, 0.16);
            color: #9a6f12;
        }

        .wm-scout-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .wm-scout-kpi {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-scout-kpi-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-scout-kpi-value {
            margin: 0.45rem 0 0;
            color: #2d2a26;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .wm-scout-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .wm-scout-section-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 1.02rem;
            color: #2d2a26;
        }

        .wm-scout-section-meta {
            color: #8b847d;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-scout-request-grid,
        .wm-scout-supplier-grid {
            display: grid;
            gap: 0.9rem;
        }

        .wm-scout-request-card,
        .wm-scout-supplier-card {
            padding: 1rem 1.05rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-scout-request-card.is-confirmed {
            background: rgba(83, 168, 106, 0.08);
            border-color: rgba(83, 168, 106, 0.24);
        }

        .wm-scout-card-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-scout-card-title {
            margin: 0;
            color: #2d2a26;
            font-weight: 700;
            font-size: 1rem;
        }

        .wm-scout-card-subtitle {
            margin: 0.2rem 0 0;
            color: #776f68;
            font-size: 0.86rem;
            line-height: 1.5;
        }

        .wm-scout-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.45rem;
        }

        .wm-scout-badge {
            display: inline-flex;
            align-items: center;
            min-height: 1.95rem;
            padding: 0 0.75rem;
            border-radius: 999px;
            background: rgba(104, 112, 123, 0.12);
            color: #5f6670;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-scout-badge.is-success {
            background: rgba(83, 168, 106, 0.14);
            color: #2d7a39;
        }

        .wm-scout-badge.is-warning {
            background: rgba(216, 177, 79, 0.16);
            color: #9a6f12;
        }

        .wm-scout-badge.is-danger {
            background: rgba(193, 91, 69, 0.12);
            color: #c15b45;
        }

        .wm-scout-data-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.7rem;
            margin-top: 0.95rem;
        }

        .wm-scout-data {
            padding: 0.7rem 0.8rem;
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.85);
        }

        .wm-scout-data-label {
            margin: 0;
            color: #968c82;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-scout-data-value {
            margin: 0.3rem 0 0;
            color: #2d2a26;
            font-weight: 700;
            font-size: 0.92rem;
        }

        .wm-scout-copy {
            margin-top: 0.9rem;
            display: grid;
            gap: 0.7rem;
        }

        .wm-scout-copy-block strong {
            display: block;
            margin-bottom: 0.2rem;
            color: #5e5852;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-scout-copy-block p {
            margin: 0;
            color: #6d665f;
            line-height: 1.7;
            white-space: pre-line;
        }

        .wm-scout-attachments {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            margin-top: 0.9rem;
        }

        .wm-scout-attachment {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
            padding: 0 0.8rem;
            border-radius: 999px;
            background: rgba(46, 74, 98, 0.08);
            color: #2e4a62;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .wm-scout-attachment.is-remove {
            border: 0;
            cursor: pointer;
            background: rgba(193, 91, 69, 0.12);
            color: #c15b45;
        }

        .wm-scout-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            margin-top: 1rem;
        }

        .wm-scout-inline-form {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid #ddd2c5;
            background: rgba(255, 255, 255, 0.92);
            display: grid;
            gap: 0.85rem;
        }

        .wm-scout-inline-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .wm-scout-label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-scout-textarea {
            width: 100%;
            min-height: 7rem;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0.8rem 0.95rem;
            color: #2d2a26;
            resize: vertical;
        }

        .wm-scout-search-form {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) repeat(3, minmax(0, 1fr)) auto;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .wm-scout-field,
        .wm-scout-select {
            width: 100%;
            min-height: 2.9rem;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0 0.95rem;
            color: #2d2a26;
        }

        .wm-scout-field:focus,
        .wm-scout-select:focus {
            outline: none;
            border-color: #2e4a62;
            box-shadow: 0 0 0 3px rgba(46, 74, 98, 0.08);
        }

        .wm-scout-empty {
            padding: 1rem 1.05rem;
            border-radius: 1rem;
            border: 1px dashed #e1d8cf;
            background: #fbf8f4;
            color: #746d66;
        }

        @media (max-width: 1280px) {
            .wm-event-top-head,
            .wm-scout-search-form,
            .wm-scout-kpis,
            .wm-scout-data-grid,
            .wm-scout-inline-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .wm-event-top-side,
            .wm-scout-card-head {
                flex-direction: column;
                align-items: stretch;
            }

            .wm-event-summary-chip,
            .wm-event-countdown {
                width: 100%;
            }

            .wm-scout-badges {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="wm-scout-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'budget',
        ])

        <section class="wm-event-card wm-scout-summary">
            <div class="wm-scout-topline">
                <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('budget', ['record' => $record]) }}" class="wm-scout-backlink">
                    ← Back to budget recap
                </a>

                <span class="wm-scout-status {{ $budget->budget_status === \App\Models\CategoryBudget::STATUS_CONFIRMED ? 'is-confirmed' : ($budget->budget_status === \App\Models\CategoryBudget::STATUS_IN_EVALUATION ? 'is-evaluation' : '') }}">
                    {{ \App\Models\CategoryBudget::STATUS_OPTIONS[$budget->budget_status] ?? $budget->budget_status }}
                </span>
            </div>

            <h3 class="wm-scout-title">{{ $summary['label'] }}</h3>
            <p class="wm-scout-note">
                Search suppliers in this category, register sent requests, collect responses and mark the accepted quote.
                When a quote is accepted, the category budget turns green in the recap page.
            </p>

            <div class="wm-scout-kpis">
                <div class="wm-scout-kpi">
                    <p class="wm-scout-kpi-label">Couple budget</p>
                    <p class="wm-scout-kpi-value">{{ $summary['couple_budget'] !== null ? 'EUR ' . number_format($summary['couple_budget'], 2, ',', '.') : '—' }}</p>
                </div>
                <div class="wm-scout-kpi">
                    <p class="wm-scout-kpi-label">Estimate</p>
                    <p class="wm-scout-kpi-value">EUR {{ number_format($summary['estimated_amount'], 2, ',', '.') }}</p>
                </div>
                <div class="wm-scout-kpi">
                    <p class="wm-scout-kpi-label">Working quote</p>
                    <p class="wm-scout-kpi-value">{{ $summary['comparison_amount'] !== null ? 'EUR ' . number_format($summary['comparison_amount'], 2, ',', '.') : '—' }}</p>
                </div>
                <div class="wm-scout-kpi">
                    <p class="wm-scout-kpi-label">Confirmed</p>
                    <p class="wm-scout-kpi-value">{{ $summary['final_amount'] !== null ? 'EUR ' . number_format($summary['final_amount'], 2, ',', '.') : '—' }}</p>
                </div>
                <div class="wm-scout-kpi">
                    <p class="wm-scout-kpi-label">Requests</p>
                    <p class="wm-scout-kpi-value">{{ $summary['proposal_count'] }} / {{ $summary['responses_count'] }} responses</p>
                </div>
            </div>
        </section>

        <section class="wm-event-card wm-scout-request-list">
            <div class="wm-scout-section-head">
                <h3 class="wm-scout-section-title">Requests sent</h3>
                <span class="wm-scout-section-meta">{{ $requests->count() }} tracked suppliers</span>
            </div>

            @if ($requests->isEmpty())
                <div class="wm-scout-empty">No supplier requests have been registered for this category yet.</div>
            @else
                <div class="wm-scout-request-grid">
                    @foreach ($requests as $proposal)
                        @php
                            $isConfirmed = $proposal->proposal_status === \App\Models\CategoryBudgetSupplier::STATUS_CONFIRMED;
                            $availabilityBadgeClass = match ($proposal->availability_status) {
                                'available' => 'is-success',
                                'unavailable' => 'is-danger',
                                default => 'is-warning',
                            };
                        @endphp
                        <article class="wm-scout-request-card {{ $isConfirmed ? 'is-confirmed' : '' }}">
                            <div class="wm-scout-card-head">
                                <div>
                                    <h4 class="wm-scout-card-title">{{ $proposal->supplier?->name ?? 'Supplier' }}</h4>
                                    <p class="wm-scout-card-subtitle">
                                        {{ collect([$proposal->supplier?->service_area, $proposal->supplier?->city])->filter()->implode(' • ') ?: 'No area specified' }}
                                    </p>
                                </div>

                                <div class="wm-scout-badges">
                                    <span class="wm-scout-badge {{ $availabilityBadgeClass }}">
                                        {{ \App\Models\CategoryBudgetSupplier::AVAILABILITY_STATUS_OPTIONS[$proposal->availability_status] ?? $proposal->availability_status }}
                                    </span>
                                    <span class="wm-scout-badge {{ $isConfirmed ? 'is-success' : 'is-warning' }}">
                                        {{ \App\Models\CategoryBudgetSupplier::PROPOSAL_STATUS_OPTIONS[$proposal->proposal_status] ?? $proposal->proposal_status }}
                                    </span>
                                    <span class="wm-scout-badge">
                                        {{ \App\Models\CategoryBudgetSupplier::SCOUTING_STATUS_OPTIONS[$proposal->scouting_status] ?? $proposal->scouting_status }}
                                    </span>
                                </div>
                            </div>

                            <div class="wm-scout-data-grid">
                                <div class="wm-scout-data">
                                    <p class="wm-scout-data-label">Requested</p>
                                    <p class="wm-scout-data-value">{{ $proposal->requested_at?->format('d/m/Y H:i') ?? '—' }}</p>
                                </div>
                                <div class="wm-scout-data">
                                    <p class="wm-scout-data-label">Responded</p>
                                    <p class="wm-scout-data-value">{{ $proposal->responded_at?->format('d/m/Y H:i') ?? '—' }}</p>
                                </div>
                                <div class="wm-scout-data">
                                    <p class="wm-scout-data-label">Quote</p>
                                    <p class="wm-scout-data-value">{{ $proposal->proposed_amount !== null ? 'EUR ' . number_format((float) $proposal->proposed_amount, 2, ',', '.') : '—' }}</p>
                                </div>
                                <div class="wm-scout-data">
                                    <p class="wm-scout-data-label">Accepted</p>
                                    <p class="wm-scout-data-value">{{ $proposal->confirmed_at?->format('d/m/Y') ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="wm-scout-copy">
                                @if (filled($proposal->request_text))
                                    <div class="wm-scout-copy-block">
                                        <strong>Request</strong>
                                        <p>{{ $proposal->request_text }}</p>
                                    </div>
                                @endif

                                @if (filled($proposal->response_text))
                                    <div class="wm-scout-copy-block">
                                        <strong>Response</strong>
                                        <p>{{ $proposal->response_text }}</p>
                                    </div>
                                @endif

                                @if (filled($proposal->proposal_summary))
                                    <div class="wm-scout-copy-block">
                                        <strong>Proposal summary</strong>
                                        <p>{{ $proposal->proposal_summary }}</p>
                                    </div>
                                @endif

                                @if (filled($proposal->costs_and_conditions))
                                    <div class="wm-scout-copy-block">
                                        <strong>Costs and conditions</strong>
                                        <p>{{ $proposal->costs_and_conditions }}</p>
                                    </div>
                                @endif
                            </div>

                            @if ($proposal->projectDocuments->where('type', \App\Models\ProjectDocument::TYPE_QUOTE)->isNotEmpty())
                                <div class="wm-scout-attachments">
                                    @foreach ($proposal->projectDocuments->where('type', \App\Models\ProjectDocument::TYPE_QUOTE) as $document)
                                        <a
                                            href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}"
                                            target="_blank"
                                            class="wm-scout-attachment"
                                        >
                                            {{ $document->title }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <div class="wm-scout-actions">
                                <x-filament::button color="gray" size="sm" wire:click="openRecordResponseModal({{ $proposal->id }})">
                                    Record response
                                </x-filament::button>

                                @if ($proposal->hasResponse() && ! $isConfirmed)
                                    <x-filament::button color="success" size="sm" wire:click="openAcceptProposalModal({{ $proposal->id }})">
                                        Mark accepted quote
                                    </x-filament::button>
                                @elseif ($isConfirmed)
                                    <a
                                        href="{{ \App\Filament\Resources\ProjectResource::getUrl('budget-manage', ['record' => $record, 'categoryBudget' => $budget]) }}"
                                        class="wm-scout-attachment"
                                    >
                                        Manage
                                    </a>
                                @endif
                            </div>

                            @if ($responseProposalId === $proposal->id)
                                <div class="wm-scout-inline-form">
                                    <div class="wm-scout-inline-grid">
                                        <div>
                                            <label class="wm-scout-label" for="responded-at-{{ $proposal->id }}">Response received at</label>
                                            <input
                                                id="responded-at-{{ $proposal->id }}"
                                                type="datetime-local"
                                                class="wm-scout-field"
                                                wire:model="responseForm.responded_at"
                                            >
                                        </div>

                                        <div>
                                            <label class="wm-scout-label" for="availability-status-{{ $proposal->id }}">Availability</label>
                                            <select
                                                id="availability-status-{{ $proposal->id }}"
                                                class="wm-scout-select"
                                                wire:model="responseForm.availability_status"
                                            >
                                                @foreach (\App\Models\CategoryBudgetSupplier::AVAILABILITY_STATUS_OPTIONS as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="wm-scout-inline-grid">
                                        <div>
                                            <label class="wm-scout-label" for="proposed-amount-{{ $proposal->id }}">Proposed amount</label>
                                            <input
                                                id="proposed-amount-{{ $proposal->id }}"
                                                type="number"
                                                step="0.01"
                                                class="wm-scout-field"
                                                wire:model="responseForm.proposed_amount"
                                            >
                                        </div>

                                        <div>
                                            <label class="wm-scout-label" for="response-scouting-status-{{ $proposal->id }}">Scouting status</label>
                                            <select
                                                id="response-scouting-status-{{ $proposal->id }}"
                                                class="wm-scout-select"
                                                wire:model="responseForm.scouting_status"
                                            >
                                                @foreach (\App\Models\CategoryBudgetSupplier::SCOUTING_STATUS_OPTIONS as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="wm-scout-label" for="proposal-status-{{ $proposal->id }}">Proposal status</label>
                                        <select
                                            id="proposal-status-{{ $proposal->id }}"
                                            class="wm-scout-select"
                                            wire:model="responseForm.proposal_status"
                                        >
                                            @foreach (collect(\App\Models\CategoryBudgetSupplier::PROPOSAL_STATUS_OPTIONS)->except([\App\Models\CategoryBudgetSupplier::STATUS_CONFIRMED]) as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="wm-scout-label" for="proposal-summary-{{ $proposal->id }}">Proposal summary</label>
                                        <textarea
                                            id="proposal-summary-{{ $proposal->id }}"
                                            class="wm-scout-textarea"
                                            wire:model="responseForm.proposal_summary"
                                        ></textarea>
                                    </div>

                                    <div>
                                        <label class="wm-scout-label" for="response-text-{{ $proposal->id }}">Response text</label>
                                        <textarea
                                            id="response-text-{{ $proposal->id }}"
                                            class="wm-scout-textarea"
                                            wire:model="responseForm.response_text"
                                        ></textarea>
                                    </div>

                                    <div>
                                        <label class="wm-scout-label" for="costs-and-conditions-{{ $proposal->id }}">Costs and conditions</label>
                                        <textarea
                                            id="costs-and-conditions-{{ $proposal->id }}"
                                            class="wm-scout-textarea"
                                            wire:model="responseForm.costs_and_conditions"
                                        ></textarea>
                                    </div>

                                    <div class="wm-scout-inline-grid">
                                        <div>
                                            <label class="wm-scout-label" for="proposed-dates-{{ $proposal->id }}">Proposed dates</label>
                                            <textarea
                                                id="proposed-dates-{{ $proposal->id }}"
                                                class="wm-scout-textarea"
                                                wire:model="responseForm.proposed_dates"
                                            ></textarea>
                                        </div>

                                        <div>
                                            <label class="wm-scout-label" for="location-availability-dates-{{ $proposal->id }}">Location availability dates</label>
                                            <textarea
                                                id="location-availability-dates-{{ $proposal->id }}"
                                                class="wm-scout-textarea"
                                                wire:model="responseForm.location_available_dates"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="wm-scout-label" for="response-notes-{{ $proposal->id }}">Notes</label>
                                        <textarea
                                            id="response-notes-{{ $proposal->id }}"
                                            class="wm-scout-textarea"
                                            wire:model="responseForm.notes"
                                        ></textarea>
                                    </div>

                                    @if (count($responseExistingAttachments))
                                        <div>
                                            <span class="wm-scout-label">Existing attachments</span>
                                            <div class="wm-scout-attachments">
                                                @foreach ($responseExistingAttachments as $attachment)
                                                    <a
                                                        href="{{ $attachment['url'] }}"
                                                        target="_blank"
                                                        class="wm-scout-attachment"
                                                    >
                                                        {{ $attachment['title'] }}
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="wm-scout-attachment is-remove"
                                                        wire:click="removeExistingResponseAttachment({{ $attachment['id'] }})"
                                                    >
                                                        Remove
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="wm-scout-label" for="response-uploads-{{ $proposal->id }}">New attachments</label>
                                        <input
                                            id="response-uploads-{{ $proposal->id }}"
                                            type="file"
                                            multiple
                                            class="wm-scout-field"
                                            wire:model="responseUploads"
                                        >
                                    </div>

                                    <div class="wm-scout-actions" style="margin-top: 0;">
                                        <x-filament::button wire:click="saveRecordResponse">
                                            Save response
                                        </x-filament::button>

                                        <x-filament::button color="gray" wire:click="cancelRecordResponse">
                                            Cancel
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="wm-event-card wm-scout-search">
            <div class="wm-scout-section-head">
                <h3 class="wm-scout-section-title">Supplier search</h3>
                <span class="wm-scout-section-meta">
                    @if ($budget->budget_status === \App\Models\CategoryBudget::STATUS_HYPOTHETICAL)
                        Start scouting
                    @else
                        Add more requests
                    @endif
                </span>
            </div>

            <div class="wm-scout-actions" style="margin-top: 0; margin-bottom: 1rem;">
                <x-filament::button color="gray" wire:click="openCreateSupplierModal">
                    Add supplier
                </x-filament::button>
            </div>

            <div class="wm-scout-search-form">
                <input
                    type="text"
                    class="wm-scout-field"
                    placeholder="Search name, area, city, contact or style"
                    wire:model.live.debounce.400ms="supplierFilters.search"
                >

                <select class="wm-scout-select" wire:model.live="supplierFilters.service_area">
                    <option value="">All areas</option>
                    @foreach ($this->getServiceAreaOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select class="wm-scout-select" wire:model.live="supplierFilters.city">
                    <option value="">All cities</option>
                    @foreach ($this->getCityOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select class="wm-scout-select" wire:model.live="supplierFilters.price_range">
                    <option value="">All price ranges</option>
                    @foreach ($this->getPriceRangeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <x-filament::button color="gray" wire:click="resetSupplierFilters">
                    Reset
                </x-filament::button>
            </div>

            @if ($supplierResults->isEmpty())
                <div class="wm-scout-empty">No suppliers match the current filters for this category.</div>
            @else
                <div class="wm-scout-supplier-grid">
                    @foreach ($supplierResults as $supplier)
                        @php
                            $existingProposal = $requests->firstWhere('supplier_id', $supplier->id);
                        @endphp
                        <article class="wm-scout-supplier-card">
                            <div class="wm-scout-card-head">
                                <div>
                                    <h4 class="wm-scout-card-title">{{ $supplier->name }}</h4>
                                    <p class="wm-scout-card-subtitle">
                                        {{ collect([$supplier->service_area, $supplier->city, $supplier->province])->filter()->implode(' • ') ?: 'No area specified' }}
                                    </p>
                                </div>

                                @if ($existingProposal)
                                    <span class="wm-scout-badge is-warning">
                                        {{ \App\Models\CategoryBudgetSupplier::PROPOSAL_STATUS_OPTIONS[$existingProposal->proposal_status] ?? 'Tracked' }}
                                    </span>
                                @endif
                            </div>

                            <div class="wm-scout-data-grid">
                                <div class="wm-scout-data">
                                    <p class="wm-scout-data-label">Contact</p>
                                    <p class="wm-scout-data-value">{{ $supplier->contact_person ?: '—' }}</p>
                                </div>
                                <div class="wm-scout-data">
                                    <p class="wm-scout-data-label">Email</p>
                                    <p class="wm-scout-data-value">{{ $supplier->email ?: '—' }}</p>
                                </div>
                                <div class="wm-scout-data">
                                    <p class="wm-scout-data-label">Phone</p>
                                    <p class="wm-scout-data-value">{{ $supplier->phone ?: '—' }}</p>
                                </div>
                                <div class="wm-scout-data">
                                    <p class="wm-scout-data-label">Price range</p>
                                    <p class="wm-scout-data-value">{{ $supplier->price_range ?: '—' }}</p>
                                </div>
                            </div>

                            @if (filled($supplier->style_description) || filled($supplier->internal_notes))
                                <div class="wm-scout-copy">
                                    @if (filled($supplier->style_description))
                                        <div class="wm-scout-copy-block">
                                            <strong>Style</strong>
                                            <p>{{ $supplier->style_description }}</p>
                                        </div>
                                    @endif

                                    @if (filled($supplier->internal_notes))
                                        <div class="wm-scout-copy-block">
                                            <strong>Internal notes</strong>
                                            <p>{{ $supplier->internal_notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="wm-scout-actions">
                                <x-filament::button
                                    color="{{ $existingProposal ? 'gray' : 'primary' }}"
                                    wire:click="startSendRequest({{ $supplier->id }})"
                                >
                                    {{ $existingProposal ? 'Update request' : 'Request sent' }}
                                </x-filament::button>
                            </div>

                            @if ($requestSupplierId === $supplier->id)
                                <div class="wm-scout-inline-form">
                                    <div class="wm-scout-inline-grid">
                                        <div>
                                            <label class="wm-scout-label" for="requested-at-{{ $supplier->id }}">Request sent at</label>
                                            <input
                                                id="requested-at-{{ $supplier->id }}"
                                                type="datetime-local"
                                                class="wm-scout-field"
                                                wire:model="requestForm.requested_at"
                                            >
                                        </div>

                                        <div>
                                            <label class="wm-scout-label" for="scouting-status-{{ $supplier->id }}">Scouting status</label>
                                            <select
                                                id="scouting-status-{{ $supplier->id }}"
                                                class="wm-scout-select"
                                                wire:model="requestForm.scouting_status"
                                            >
                                                @foreach (\App\Models\CategoryBudgetSupplier::SCOUTING_STATUS_OPTIONS as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="wm-scout-label" for="request-text-{{ $supplier->id }}">Request text</label>
                                        <textarea
                                            id="request-text-{{ $supplier->id }}"
                                            class="wm-scout-textarea"
                                            wire:model="requestForm.request_text"
                                        ></textarea>
                                    </div>

                                    <div>
                                        <label class="wm-scout-label" for="planner-notes-{{ $supplier->id }}">Planner notes</label>
                                        <textarea
                                            id="planner-notes-{{ $supplier->id }}"
                                            class="wm-scout-textarea"
                                            wire:model="requestForm.planner_notes"
                                        ></textarea>
                                    </div>

                                    <div class="wm-scout-actions" style="margin-top: 0;">
                                        <x-filament::button wire:click="saveSendRequest">
                                            Save request
                                        </x-filament::button>

                                        <x-filament::button color="gray" wire:click="cancelSendRequest">
                                            Cancel
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
