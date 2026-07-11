<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $supplierProposals = $this->getSupplierProposals();
        $summary = $this->getSuppliersSummary();
        $payments = $this->getProjectPayments();
        $paymentsSummary = $this->getPaymentsSummary();
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

        .wm-supplier-payment-breakdown {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.45rem;
            margin-top: 0.7rem;
            padding-top: 0.65rem;
            border-top: 1px solid #ece5dd;
        }

        .wm-supplier-payment-breakdown span {
            display: block;
            color: #8b847d;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-supplier-payment-breakdown strong {
            display: block;
            margin-top: 0.18rem;
            color: #2d2a26;
            font-size: 0.86rem;
            line-height: 1.25;
        }

        .wm-suppliers-workspace {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(22rem, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .wm-supplier-list {
            display: grid;
            gap: 0.75rem;
        }

        .wm-supplier-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.8rem 1rem;
            align-items: center;
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
            font-size: 1rem;
        }

        .wm-supplier-copy {
            margin: 0.3rem 0 0;
            color: #6f6861;
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .wm-supplier-amount {
            color: #2d2a26;
            font-weight: 700;
            white-space: nowrap;
        }

        .wm-supplier-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
            grid-column: 1 / -1;
        }

        .wm-supplier-mini {
            padding: 0.62rem 0.7rem;
            border-radius: 0.75rem;
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
            padding: 0 0.9rem;
            border-radius: 999px;
            background: #2e4a62;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }

        .wm-payments-panel {
            position: sticky;
            top: 1rem;
            display: grid;
            gap: 0.9rem;
            padding: 1rem;
        }

        .wm-payments-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .wm-payments-title {
            margin: 0.28rem 0 0;
            color: #2d2a26;
            font-size: 1rem;
            font-weight: 800;
        }

        .wm-payments-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.1rem;
            border: 1px solid #ddd2c5;
            border-radius: 999px;
            background: #fff;
            padding: 0 0.8rem;
            color: #4d473f;
            font-size: 0.74rem;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
        }

        .wm-payments-head-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.45rem;
        }

        .wm-payments-toggle.is-download {
            background: #2e4a62;
            border-color: #2e4a62;
            color: #fff;
        }

        .wm-payments-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.45rem;
        }

        .wm-payments-kpi {
            border-radius: 0.75rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
            padding: 0.58rem 0.62rem;
        }

        .wm-payments-kpi strong {
            display: block;
            color: #2d2a26;
            font-size: 0.98rem;
            line-height: 1.1;
        }

        .wm-payments-kpi span {
            display: block;
            margin-top: 0.18rem;
            color: #8b847d;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .wm-payment-list {
            display: grid;
            gap: 0.55rem;
            max-height: 42rem;
            overflow: auto;
            padding-right: 0.15rem;
        }

        .wm-payment-item {
            display: grid;
            gap: 0.48rem;
            padding: 0.78rem;
            border-radius: 0.85rem;
            border: 1px solid #ece5dd;
            background: #fff;
        }

        .wm-payment-item.is-clickable {
            cursor: pointer;
            transition: border-color 140ms ease, box-shadow 140ms ease, transform 140ms ease;
        }

        .wm-payment-item.is-clickable:hover {
            border-color: rgba(46, 74, 98, 0.28);
            box-shadow: 0 12px 24px rgba(45, 42, 38, 0.08);
            transform: translateY(-1px);
        }

        .wm-payment-item.is-paid {
            opacity: 0.62;
        }

        .wm-payment-item.is-overdue {
            border-color: rgba(197, 65, 65, 0.42);
            background: rgba(197, 65, 65, 0.07);
        }

        .wm-payment-topline {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .wm-payment-title {
            margin: 0;
            color: #2d2a26;
            font-size: 0.86rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .wm-payment-amount {
            color: #2d2a26;
            font-size: 0.82rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .wm-payment-meta {
            margin: 0;
            color: #746d66;
            font-size: 0.76rem;
            line-height: 1.45;
        }

        .wm-payment-due {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
        }

        .wm-payment-due-date {
            color: #2d2a26;
            font-size: 0.92rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .wm-payment-countdown {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border-radius: 999px;
            padding: 0.18rem 0.48rem;
            background: rgba(201, 169, 106, 0.16);
            color: #8b6423;
            font-size: 0.66rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .wm-payment-status {
            display: inline-flex;
            width: fit-content;
            border-radius: 999px;
            padding: 0.22rem 0.52rem;
            background: rgba(46, 74, 98, 0.1);
            color: #2e4a62;
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-payment-status.is-paid {
            background: rgba(122, 143, 123, 0.14);
            color: #4c6d4e;
        }

        .wm-payment-status.is-overdue {
            background: rgba(197, 65, 65, 0.13);
            color: #9b2f2f;
        }

        .wm-payment-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            align-items: center;
        }

        .wm-payment-link {
            appearance: none;
            border: 0;
            background: transparent;
            padding: 0;
            color: #2e4a62;
            font-size: 0.74rem;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
        }

        .wm-payment-registration {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.2rem;
            padding: 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #e8e3dc;
            background: #fbf8f4;
        }

        .wm-payment-registration-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.65rem;
        }

        .wm-payment-registration label {
            display: block;
            margin-bottom: 0.28rem;
            color: #746d66;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-payment-registration input {
            width: 100%;
            border: 1px solid #ded4c8;
            border-radius: 0.65rem;
            background: #fff;
            padding: 0.55rem 0.65rem;
            color: #2d2a26;
            font-size: 0.82rem;
        }

        .wm-supplier-empty {
            padding: 1.1rem 1.15rem;
            color: #746d66;
        }

        @media (max-width: 1100px) {
            .wm-suppliers-summary,
            .wm-suppliers-workspace,
            .wm-event-top-head,
            .wm-event-date-grid {
                grid-template-columns: 1fr;
            }

            .wm-payments-panel {
                position: static;
            }

            .wm-supplier-card {
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
                <div class="wm-supplier-payment-breakdown">
                    <div>
                        <span>Paid</span>
                        <strong>EUR {{ number_format($summary['payments_paid_total'], 2, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span>To do</span>
                        <strong>EUR {{ number_format($summary['payments_unpaid_total'], 2, ',', '.') }}</strong>
                    </div>
                </div>
            </article>
            <article class="wm-event-card wm-supplier-stat">
                <p class="wm-supplier-stat-label">Communications</p>
                <p class="wm-supplier-stat-value">{{ $summary['communications_count'] }}</p>
            </article>
        </section>

        <section class="wm-suppliers-workspace">
            <div>
                @if ($supplierProposals->isEmpty())
                    <section class="wm-event-card wm-supplier-empty">
                        No confirmed suppliers yet. Confirm a quote from Budget to move it into this operational area.
                    </section>
                @else
                    <section class="wm-supplier-list">
                        @foreach ($supplierProposals as $proposal)
                            <article class="wm-event-card wm-supplier-card">
                                <div class="wm-supplier-head">
                                    <div>
                                        <p class="wm-supplier-label">{{ $proposal->category?->label ?? 'Category' }}</p>
                                        <h3 class="wm-supplier-title">{{ $proposal->supplier?->name ?? 'Supplier' }}</h3>
                                        <p class="wm-supplier-copy">
                                            {{ collect([$proposal->supplier?->service_area, $proposal->supplier?->city])->filter()->implode(' • ') ?: 'No area specified' }}
                                        </p>
                                    </div>
                                    <span class="wm-supplier-amount">
                                        {{ $proposal->proposed_amount !== null ? 'EUR ' . number_format((float) $proposal->proposed_amount, 2, ',', '.') : '—' }}
                                    </span>
                                </div>

                                <a
                                    href="{{ \App\Filament\Resources\ProjectResource::getUrl('supplier-manage', ['record' => $record, 'proposal' => $proposal->id]) }}"
                                    class="wm-supplier-action"
                                >
                                    Manage {{ $proposal->supplier?->name ?? 'supplier' }}
                                </a>

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
                            </article>
                        @endforeach
                    </section>
                @endif
            </div>

            <aside class="wm-event-card wm-payments-panel">
                <div class="wm-payments-head">
                    <div>
                        <p class="wm-supplier-label">Payment schedule</p>
                        <h3 class="wm-payments-title">Upcoming deadlines</h3>
                    </div>
                    <div class="wm-payments-head-actions">
                        <a class="wm-payments-toggle is-download" href="{{ route('admin.projects.payments.pdf', ['project' => $record]) }}" target="_blank" rel="noopener">
                            Payments PDF
                        </a>
                        <button type="button" class="wm-payments-toggle" wire:click="$toggle('hidePaidPayments')">
                            {{ $this->hidePaidPayments ? 'Show paid' : 'Hide paid' }}
                        </button>
                    </div>
                </div>

                <div class="wm-payments-kpis">
                    <div class="wm-payments-kpi">
                        <strong>{{ $paymentsSummary['visible_count'] }}</strong>
                        <span>Visible</span>
                    </div>
                    <div class="wm-payments-kpi">
                        <strong>{{ $paymentsSummary['unpaid_count'] }}</strong>
                        <span>Unpaid</span>
                    </div>
                    <div class="wm-payments-kpi">
                        <strong>{{ $paymentsSummary['overdue_count'] }}</strong>
                        <span>Overdue</span>
                    </div>
                </div>

                @if ($payments->isEmpty())
                    <div class="wm-supplier-empty">
                        {{ $paymentsSummary['total_count'] === 0 ? 'No payments scheduled yet.' : 'No payments match the current filter.' }}
                    </div>
                @else
                    <div class="wm-payment-list">
                        @foreach ($payments as $payment)
                            @php
                                $isPaid = $payment->payment_status === \App\Models\Payment::STATUS_PAID;
                                $isOverdue = ! $isPaid && $payment->due_date && $payment->due_date->copy()->startOfDay()->lt(now()->startOfDay());
                                $statusLabel = $isPaid ? 'Paid' : ($isOverdue ? 'Overdue' : 'Unpaid');
                                $daysUntilDue = (! $isPaid && $payment->due_date)
                                    ? now()->startOfDay()->diffInDays($payment->due_date->copy()->startOfDay(), false)
                                    : null;
                                $showDueSoon = $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 30;
                                $proposal = $payment->categoryBudgetSupplier;
                                $isOpen = (bool) ($this->openPaymentRegistrations[$payment->id] ?? false);
                            @endphp
                            <article
                                class="wm-payment-item {{ ! $isPaid && ! $isOpen ? 'is-clickable' : '' }} {{ $isPaid ? 'is-paid' : '' }} {{ $isOverdue ? 'is-overdue' : '' }}"
                                @if (! $isPaid && ! $isOpen)
                                    wire:click="startPaymentRegistration({{ $payment->id }})"
                                @endif
                            >
                                <div class="wm-payment-topline">
                                    <p class="wm-payment-title">{{ $payment->reason ?: 'Payment' }}</p>
                                    <span class="wm-payment-amount">EUR {{ number_format((float) $payment->amount, 2, ',', '.') }}</span>
                                </div>
                                <div class="wm-payment-due">
                                    <span class="wm-payment-due-date">Due {{ $payment->due_date?->format('M j, Y') ?? 'not set' }}</span>
                                    @if ($showDueSoon)
                                        <span class="wm-payment-countdown">
                                            {{ $daysUntilDue === 0 ? 'Due today' : $daysUntilDue . ' days left' }}
                                        </span>
                                    @endif
                                </div>
                                <p class="wm-payment-meta">
                                    @if ($payment->supplier?->name)
                                        {{ $payment->supplier->name }}
                                    @endif
                                    @if ($proposal?->category)
                                        {{ $payment->supplier?->name ? ' • ' : '' }}{{ $proposal->category->label }}
                                    @endif
                                </p>
                                <span class="wm-payment-status {{ $isPaid ? 'is-paid' : '' }} {{ $isOverdue ? 'is-overdue' : '' }}">{{ $statusLabel }}</span>

                                <div class="wm-payment-actions">
                                    @if ($payment->paymentReceiptDocument)
                                        <a
                                            href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->paymentReceiptDocument->file_path) }}"
                                            target="_blank"
                                            class="wm-payment-link"
                                            onclick="event.stopPropagation()"
                                        >
                                            Open receipt
                                        </a>
                                    @endif
                                    @if (! $isPaid && ! $isOpen)
                                        <button type="button" class="wm-payment-link" wire:click.stop="startPaymentRegistration({{ $payment->id }})">
                                            Register payment
                                        </button>
                                    @endif
                                </div>

                                @if (! $isPaid && $isOpen)
                                    <div class="wm-payment-registration">
                                        <div class="wm-payment-registration-grid">
                                            <div>
                                                <label for="project-payment-paid-at-{{ $payment->id }}">Payment date</label>
                                                <input id="project-payment-paid-at-{{ $payment->id }}" type="date" wire:model="paymentCompletionForms.{{ $payment->id }}.paid_at">
                                            </div>
                                            <div>
                                                <label for="project-payment-receipt-{{ $payment->id }}">Payment receipt</label>
                                                <input id="project-payment-receipt-{{ $payment->id }}" type="file" wire:model="paymentCompletionReceiptUploads.{{ $payment->id }}">
                                            </div>
                                        </div>

                                        <div class="wm-payment-actions">
                                            <x-filament::button color="primary" wire:click="registerScheduledPayment({{ $payment->id }})">Confirm payment</x-filament::button>
                                            <button type="button" class="wm-payment-link" wire:click="cancelPaymentRegistration({{ $payment->id }})">Cancel</button>
                                        </div>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </aside>
        </section>
    </div>
</x-filament-panels::page>
