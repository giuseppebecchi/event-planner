<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $budgetSummary = $this->getBudgetSummary();
        $budgetRows = $this->getBudgetRows();
        $isCustomer = auth()->user()?->isCustomer();
    @endphp

    <style>
        .wm-budget-page {
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

        .wm-budget-summary {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(20rem, 0.9fr);
            gap: 1rem;
        }

        .wm-budget-hero,
        .wm-budget-sidebar-card,
        .wm-budget-table-card {
            padding: 1.2rem 1.25rem;
        }

        .wm-budget-kicker {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-budget-total {
            margin: 0.45rem 0 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: clamp(2rem, 3vw, 2.8rem);
            line-height: 1;
        }

        .wm-budget-hero-text {
            margin: 0.5rem 0 0;
            max-width: 42rem;
            color: #655f59;
            line-height: 1.7;
        }

        .wm-budget-hero-grid,
        .wm-budget-sidebar-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .wm-budget-mini {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-budget-mini-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-budget-mini-value {
            margin: 0.45rem 0 0;
            color: #2d2a26;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .wm-budget-venue-note {
            margin-top: 1rem;
            padding: 0.85rem 0.95rem;
            border-radius: 0.95rem;
            border: 1px solid rgba(201, 169, 106, 0.34);
            background: rgba(201, 169, 106, 0.09);
            color: #5f5953;
        }

        .wm-budget-venue-note strong {
            display: block;
            color: #8b6423;
            font-size: 0.74rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-budget-venue-note span {
            display: block;
            margin-top: 0.28rem;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .wm-budget-layout {
            display: block;
        }

        .wm-budget-table-header,
        .wm-budget-sidebar-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .wm-budget-table-heading {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .wm-budget-table-title,
        .wm-budget-sidebar-heading {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 1.02rem;
            color: #2d2a26;
        }

        .wm-budget-table-note {
            color: #8b847d;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-budget-table-wrap {
            overflow-x: auto;
        }

        .wm-budget-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 68rem;
        }

        .wm-budget-table th {
            padding: 0 0 0.85rem;
            color: #9a9086;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            text-align: left;
            border-bottom: 1px solid #ece5dd;
        }

        .wm-budget-table td {
            padding: 1rem 0;
            border-bottom: 1px solid #f1ebe4;
            vertical-align: top;
        }

        .wm-budget-table tr.is-confirmed td {
            background: rgba(83, 168, 106, 0.06);
        }

        .wm-budget-category {
            display: flex;
            flex-direction: column;
            gap: 0.28rem;
        }

        .wm-budget-category-name {
            color: #2d2a26;
            font-weight: 700;
        }

        .wm-budget-category-meta {
            color: #7d756e;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .wm-budget-number {
            color: #2d2a26;
            font-weight: 600;
            white-space: nowrap;
        }

        .wm-budget-number.is-positive {
            color: #2d7a39;
        }

        .wm-budget-number.is-negative {
            color: #c15b45;
        }

        .wm-budget-status {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
            padding: 0 0.8rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-budget-status.is-confirmed {
            background: rgba(83, 168, 106, 0.14);
            color: #2d7a39;
        }

        .wm-budget-status.is-evaluation {
            background: rgba(216, 177, 79, 0.16);
            color: #9a6f12;
        }

        .wm-budget-status.is-hypothetical {
            background: rgba(104, 112, 123, 0.12);
            color: #5f6670;
        }

        .wm-budget-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 2.4rem;
            padding: 0 0.95rem;
            border-radius: 999px;
            background: #2e4a62;
            color: #fff;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .wm-budget-add-category {
            border: 0;
            cursor: pointer;
        }

        .wm-budget-action.is-confirmed {
            background: #2e4a62;
            color: #fff;
        }

        .wm-budget-action-inline-icon {
            width: 0.95rem;
            height: 0.95rem;
            stroke-width: 2.35;
        }

        .wm-budget-action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.9rem;
            height: 1.9rem;
            margin-left: 0.45rem;
            border: 1px solid rgba(46, 74, 98, 0.18);
            border-radius: 999px;
            background: rgba(46, 74, 98, 0.08);
            color: #2e4a62;
            cursor: pointer;
            vertical-align: middle;
            transition: background-color 120ms ease, border-color 120ms ease, color 120ms ease;
        }

        .wm-budget-action-icon:hover {
            border-color: rgba(46, 74, 98, 0.32);
            background: #2e4a62;
            color: #fff;
        }

        .wm-budget-action-icon svg {
            width: 1.05rem;
            height: 1.05rem;
            stroke-width: 2.2;
        }

        .wm-budget-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .wm-budget-accepted-list {
            display: grid;
            gap: 0.45rem;
            justify-items: end;
        }

        .wm-budget-accepted-name {
            max-width: 12rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .wm-budget-action-group {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .wm-budget-empty {
            padding: 1rem 1.05rem;
            border-radius: 1rem;
            border: 1px dashed #e1d8cf;
            background: #fbf8f4;
            color: #746d66;
        }

        @media (max-width: 1280px) {
            .wm-budget-summary,
            .wm-budget-layout {
                grid-template-columns: 1fr;
            }

            .wm-event-top-head,
            .wm-event-date-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .wm-budget-hero-grid,
            .wm-budget-sidebar-grid {
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

    <div class="wm-budget-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'budget',
        ])

        <section class="wm-budget-summary">
            <article class="wm-event-card wm-budget-hero">
                <p class="wm-budget-kicker">Budget recap</p>
                <h3 class="wm-budget-total">EUR {{ number_format($budgetSummary['estimated_total'], 2, ',', '.') }}</h3>
                <p class="wm-budget-hero-text">
                    Track each service category from the initial estimate to the working comparison and the final accepted quote{{ $budgetSummary['venue_excluded'] ? ', excluding the venue cost from recap totals' : '' }}.
                    Categories with an accepted proposal are highlighted in green.
                </p>

                <div class="wm-budget-hero-grid">
                    <div class="wm-budget-mini">
                        <p class="wm-budget-mini-label">Confirmed categories</p>
                        <p class="wm-budget-mini-value">{{ $budgetSummary['confirmed_count'] }} / {{ $budgetSummary['categories_count'] }}</p>
                    </div>
                    <div class="wm-budget-mini">
                        <p class="wm-budget-mini-label">Under evaluation</p>
                        <p class="wm-budget-mini-value">{{ $budgetSummary['in_evaluation_count'] }}</p>
                    </div>
                    <div class="wm-budget-mini">
                        <p class="wm-budget-mini-label">Completion</p>
                        <p class="wm-budget-mini-value">{{ $budgetSummary['completion'] }}%</p>
                    </div>
                </div>
            </article>

            <aside class="wm-event-card wm-budget-sidebar-card">
                <div class="wm-budget-sidebar-title">
                    <h3 class="wm-budget-sidebar-heading">Totals</h3>
                    <span class="wm-budget-table-note">Live recap</span>
                </div>

                <div class="wm-budget-sidebar-grid">
                    <div class="wm-budget-mini">
                        <p class="wm-budget-mini-label">Couple Budget</p>
                        <p class="wm-budget-mini-value">
                            {{ $budgetSummary['couple_budget'] !== null ? 'EUR ' . number_format($budgetSummary['couple_budget'], 2, ',', '.') : '—' }}
                        </p>
                    </div>
                    <div class="wm-budget-mini">
                        <p class="wm-budget-mini-label">Estimated Budget</p>
                        <p class="wm-budget-mini-value">EUR {{ number_format($budgetSummary['estimated_total'], 2, ',', '.') }}</p>
                    </div>
                    <div class="wm-budget-mini">
                        <p class="wm-budget-mini-label">Confirmed Budget</p>
                        <p class="wm-budget-mini-value">EUR {{ number_format($budgetSummary['final_total'], 2, ',', '.') }}</p>
                    </div>
                    <div class="wm-budget-mini">
                        <p class="wm-budget-mini-label">Working Budget</p>
                        <p class="wm-budget-mini-value">EUR {{ number_format($budgetSummary['comparison_total'], 2, ',', '.') }}</p>
                    </div>
                </div>

                @if ($budgetSummary['venue_excluded'])
                    <div class="wm-budget-venue-note">
                        <strong>Venue separated</strong>
                        <span>
                            Venue cost is shown in the table but excluded from these budget totals.
                            Couple budget remains the full budget amount:
                            {{ $budgetSummary['couple_budget'] !== null ? 'EUR ' . number_format($budgetSummary['couple_budget'], 2, ',', '.') : 'not defined' }}.
                            Current venue amount:
                            EUR {{ number_format($budgetSummary['venue_separated_amount'], 2, ',', '.') }}.
                        </span>
                    </div>
                @endif
            </aside>
        </section>

        <section class="wm-budget-layout">
            <article class="wm-event-card wm-budget-table-card">
                <div class="wm-budget-table-header">
                    <div class="wm-budget-table-heading">
                        <h3 class="wm-budget-table-title">Service categories</h3>
                        <span class="wm-budget-table-note">{{ $budgetSummary['all_categories_count'] }} categories</span>
                    </div>

                    @if (! $isCustomer && $this->hasAvailableServiceCategories())
                        <button type="button" class="wm-budget-action wm-budget-add-category" wire:click="mountAction('addServiceCategory')">
                            Add Service Category
                        </button>
                    @endif
                </div>

                @if ($budgetRows->isEmpty())
                    <div class="wm-budget-empty">No category budgets found for this event yet.</div>
                @else
                    <div class="wm-budget-table-wrap">
                        <table class="wm-budget-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Estimate Budget</th>
                                    <th>Confirmed Quote</th>
                                    <th>Difference</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($budgetRows as $budget)
                                    @php
                                        $confirmedProposals = $budget->supplierProposals
                                            ->where('proposal_status', \App\Models\CategoryBudgetSupplier::STATUS_CONFIRMED)
                                            ->values();
                                        $difference = $budget->amountDifference();
                                        $statusClass = match ($budget->budget_status) {
                                            \App\Models\CategoryBudget::STATUS_CONFIRMED => 'is-confirmed',
                                            \App\Models\CategoryBudget::STATUS_IN_EVALUATION => 'is-evaluation',
                                            default => 'is-hypothetical',
                                        };
                                    @endphp
                                    <tr class="{{ $budget->budget_status === \App\Models\CategoryBudget::STATUS_CONFIRMED ? 'is-confirmed' : '' }}">
                                        <td>
                                            <div class="wm-budget-category">
                                                <span class="wm-budget-category-name">{{ $budget->category?->label ?? 'Category' }}</span>
                                                <span class="wm-budget-category-meta">
                                                    {{ $budget->supplierProposals->count() }} supplier {{ \Illuminate\Support\Str::plural('request', $budget->supplierProposals->count()) }}
                                                    @if ($confirmedProposals->isNotEmpty())
                                                        • accepted: {{ $confirmedProposals->count() }}
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td class="wm-budget-number">
                                            EUR {{ number_format((float) ($budget->initial_estimated_amount ?? 0), 2, ',', '.') }}
                                            @if (! $isCustomer)
                                                <button
                                                    type="button"
                                                    class="wm-budget-action-icon"
                                                    wire:click="mountAction('editBudgetCategory', { budget: {{ $budget->id }} })"
                                                    title="Edit budget"
                                                    aria-label="Edit budget"
                                                >
                                                    <x-heroicon-o-pencil-square />
                                                </button>
                                            @endif
                                        </td>
                                        <td class="wm-budget-number">
                                            {{ $budget->final_amount !== null ? 'EUR ' . number_format((float) $budget->final_amount, 2, ',', '.') : '—' }}
                                        </td>
                                        <td class="wm-budget-number {{ $budget->final_amount !== null ? ($difference > 0 ? 'is-negative' : ($difference < 0 ? 'is-positive' : '')) : '' }}">
                                            @if ($budget->final_amount !== null)
                                                {{ $difference === 0.0 ? 'EUR 0,00' : (($difference > 0 ? '+ ' : '- ') . 'EUR ' . number_format(abs($difference), 2, ',', '.')) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <span class="wm-budget-status {{ $statusClass }}">
                                                {{ \App\Models\CategoryBudget::STATUS_OPTIONS[$budget->budget_status] ?? $budget->budget_status }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="wm-budget-actions">
                                                <a
                                                    href="{{ \App\Filament\Resources\ProjectResource::getUrl('budget-scouting', ['record' => $record, 'categoryBudget' => $budget]) }}"
                                                    class="wm-budget-action"
                                                >
                                                    Open details
                                                    <x-heroicon-o-arrow-right class="wm-budget-action-inline-icon" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                @php
                                    $totalDifference = $budgetSummary['final_total'] - $budgetSummary['confirmed_hypothetical_total'];
                                @endphp
                                <tr>
                                    <th>Totals</th>
                                    <th class="wm-budget-number">EUR {{ number_format($budgetSummary['confirmed_hypothetical_total'], 2, ',', '.') }}</th>
                                    <th class="wm-budget-number">EUR {{ number_format($budgetSummary['final_total'], 2, ',', '.') }}</th>
                                    <th class="wm-budget-number {{ $totalDifference > 0 ? 'is-negative' : ($totalDifference < 0 ? 'is-positive' : '') }}">
                                        {{ $totalDifference === 0.0 ? 'EUR 0,00' : (($totalDifference > 0 ? '+ ' : '- ') . 'EUR ' . number_format(abs($totalDifference), 2, ',', '.')) }}
                                    </th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </article>
        </section>
    </div>
</x-filament-panels::page>
