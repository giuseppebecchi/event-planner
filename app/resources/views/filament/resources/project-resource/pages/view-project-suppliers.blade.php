<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $supplierProposals = $this->getSupplierProposals();
        $summary = $this->getSuppliersSummary();
    @endphp

    <style>
        .wm-suppliers-page {
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

        .wm-suppliers-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-supplier-stat,
        .wm-supplier-card {
            padding: 1.1rem 1.15rem;
        }

        .wm-supplier-stat-label,
        .wm-supplier-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-supplier-stat-value {
            margin: 0.45rem 0 0;
            color: #2d2a26;
            font-size: 1.45rem;
            font-weight: 700;
        }

        .wm-supplier-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-supplier-card {
            display: grid;
            gap: 1rem;
        }

        .wm-supplier-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-supplier-title {
            margin: 0.25rem 0 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: 1.05rem;
        }

        .wm-supplier-copy {
            margin: 0.3rem 0 0;
            color: #6f6861;
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .wm-supplier-amount {
            color: #2d2a26;
            font-weight: 700;
            white-space: nowrap;
        }

        .wm-supplier-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.7rem;
        }

        .wm-supplier-mini {
            padding: 0.8rem 0.85rem;
            border-radius: 0.9rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-supplier-mini strong {
            display: block;
            margin-top: 0.25rem;
            color: #2d2a26;
            font-size: 1rem;
        }

        .wm-supplier-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            min-height: 2.45rem;
            padding: 0 1rem;
            border-radius: 999px;
            background: #2e4a62;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }

        .wm-supplier-empty {
            padding: 1.1rem 1.15rem;
            color: #746d66;
        }

        @media (max-width: 1100px) {
            .wm-suppliers-summary,
            .wm-supplier-grid,
            .wm-event-top-head,
            .wm-event-date-grid {
                grid-template-columns: 1fr;
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
    </style>

    <div class="wm-suppliers-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'suppliers',
        ])

        <section class="wm-suppliers-summary">
            <article class="wm-event-card wm-supplier-stat">
                <p class="wm-supplier-stat-label">Operational suppliers</p>
                <p class="wm-supplier-stat-value">{{ $summary['confirmed_count'] }}</p>
            </article>
            <article class="wm-event-card wm-supplier-stat">
                <p class="wm-supplier-stat-label">Documents</p>
                <p class="wm-supplier-stat-value">{{ $summary['contract_documents_count'] }}</p>
            </article>
            <article class="wm-event-card wm-supplier-stat">
                <p class="wm-supplier-stat-label">Payments</p>
                <p class="wm-supplier-stat-value">EUR {{ number_format($summary['payments_total'], 2, ',', '.') }}</p>
            </article>
            <article class="wm-event-card wm-supplier-stat">
                <p class="wm-supplier-stat-label">Communications</p>
                <p class="wm-supplier-stat-value">{{ $summary['communications_count'] }}</p>
            </article>
        </section>

        @if ($supplierProposals->isEmpty())
            <section class="wm-event-card wm-supplier-empty">
                No confirmed suppliers yet. Confirm a quote from Budget to move it into this operational area.
            </section>
        @else
            <section class="wm-supplier-grid">
                @foreach ($supplierProposals as $proposal)
                    <article class="wm-event-card wm-supplier-card">
                        <div class="wm-supplier-head">
                            <div>
                                <p class="wm-supplier-label">{{ $proposal->category?->label_it ?? $proposal->category?->label ?? 'Category' }}</p>
                                <h3 class="wm-supplier-title">{{ $proposal->supplier?->name ?? 'Supplier' }}</h3>
                                <p class="wm-supplier-copy">
                                    {{ collect([$proposal->supplier?->service_area, $proposal->supplier?->city])->filter()->implode(' • ') ?: 'No area specified' }}
                                </p>
                            </div>
                            <span class="wm-supplier-amount">
                                {{ $proposal->proposed_amount !== null ? 'EUR ' . number_format((float) $proposal->proposed_amount, 2, ',', '.') : '—' }}
                            </span>
                        </div>

                        <div class="wm-supplier-meta">
                            <div class="wm-supplier-mini">
                                <p class="wm-supplier-label">Docs</p>
                                <strong>{{ $proposal->projectDocuments->count() }}</strong>
                            </div>
                            <div class="wm-supplier-mini">
                                <p class="wm-supplier-label">Payments</p>
                                <strong>{{ $proposal->payments->count() }}</strong>
                            </div>
                            <div class="wm-supplier-mini">
                                <p class="wm-supplier-label">Messages</p>
                                <strong>{{ $proposal->communications->count() }}</strong>
                            </div>
                        </div>

                        <a
                            href="{{ \App\Filament\Resources\ProjectResource::getUrl('supplier-manage', ['record' => $record, 'proposal' => $proposal->id]) }}"
                            class="wm-supplier-action"
                        >
                            Manage {{ $proposal->supplier?->name ?? 'supplier' }}
                        </a>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-filament-panels::page>
