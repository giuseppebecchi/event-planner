<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $budget = $this->categoryBudgetRecord;
        $summary = $this->getBudgetSummary();
        $requests = $this->getExistingRequests();
        $supplierResults = $this->getSupplierResults();
        $isLocationCategory = $this->isLocationCategory();
        $canExportPresentationPdf = $this->canExportPresentationPdf();
        $presentationExportCount = $this->getPresentationExportCount();
        $costItemSuggestions = $this->getCostItemSuggestions();
        $comparison = $this->getProposalComparison();
        $money = fn ($amount) => $amount !== null && $amount !== '' ? 'EUR ' . number_format((float) $amount, 2, ',', '.') : '—';
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
            grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .wm-scout-kpi {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-scout-kpi.is-primary {
            background: linear-gradient(135deg, rgba(46, 74, 98, 0.98), rgba(74, 96, 103, 0.94));
            border-color: rgba(46, 74, 98, 0.2);
        }

        .wm-scout-kpi.is-primary .wm-scout-kpi-label,
        .wm-scout-kpi.is-primary .wm-scout-kpi-value {
            color: #fff;
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
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            border-bottom: 1px solid #ece5dd;
            background: linear-gradient(135deg, rgba(46, 74, 98, 0.08), rgba(201, 169, 106, 0.08));
            border: 1px solid rgba(46, 74, 98, 0.1);
            position: relative;
            overflow: hidden;
        }

        .wm-scout-section-head::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.34rem;
            background: #2e4a62;
        }

        .wm-scout-section-head > * {
            position: relative;
        }

        .wm-scout-section-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.28rem, 1.7vw, 1.72rem);
            line-height: 1.08;
            color: #2d2a26;
        }

        .wm-scout-section-kicker {
            display: inline-flex;
            align-items: center;
            min-height: 1.55rem;
            margin: 0 0 0.4rem;
            padding: 0 0.58rem;
            border-radius: 999px;
            background: rgba(46, 74, 98, 0.1);
            color: #2e4a62;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-scout-section-meta {
            color: #8b847d;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-scout-section-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: 0.7rem;
        }

        .wm-scout-export-button {
            display: inline-flex;
            align-items: center;
            gap: 0.48rem;
            min-height: 2.45rem;
            padding: 0 0.95rem;
            border-radius: 999px;
            background: #2e4a62;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-decoration: none;
            white-space: nowrap;
        }

        .wm-scout-export-button svg {
            width: 1rem;
            height: 1rem;
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

        .wm-scout-request-card {
            display: grid;
            gap: 1rem;
            padding: 0;
            overflow: hidden;
        }

        .wm-scout-supplier-card {
            display: grid;
            gap: 0.75rem;
            padding-block: 0.9rem;
        }

        .wm-scout-request-card.is-confirmed {
            background: rgba(83, 168, 106, 0.08);
            border-color: rgba(83, 168, 106, 0.24);
        }

        .wm-scout-request-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.05rem;
            border-bottom: 1px solid rgba(221, 210, 197, 0.82);
            background: linear-gradient(135deg, #fff 0%, #f4efe8 100%);
        }

        .wm-scout-request-card.is-confirmed .wm-scout-request-header {
            border-bottom-color: rgba(83, 168, 106, 0.24);
            background: linear-gradient(135deg, #fff 0%, rgba(83, 168, 106, 0.11) 100%);
        }

        .wm-scout-request-identity {
            display: grid;
            grid-template-columns: 2.9rem minmax(0, 1fr);
            gap: 0.85rem;
            align-items: center;
            min-width: 0;
        }

        .wm-scout-supplier-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.9rem;
            height: 2.9rem;
            border-radius: 0.9rem;
            background: #2e4a62;
            color: #fff;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
        }

        .wm-scout-request-card.is-confirmed .wm-scout-supplier-avatar {
            background: #2d7a39;
        }

        .wm-scout-supplier-eyebrow {
            margin: 0 0 0.18rem;
            color: #968c82;
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .wm-scout-request-body {
            display: grid;
            gap: 1rem;
            padding: 0 1.05rem 1rem;
        }

        .wm-scout-card-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-scout-supplier-card .wm-scout-card-head {
            align-items: center;
        }

        .wm-scout-card-main {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.35rem 0.9rem;
            min-width: 0;
        }

        .wm-scout-card-title {
            margin: 0;
            color: #2d2a26;
            font-weight: 700;
            font-size: 1rem;
        }

        .wm-scout-request-header .wm-scout-card-title {
            font-size: clamp(1.2rem, 1.6vw, 1.55rem);
            font-weight: 800;
            line-height: 1.12;
            overflow-wrap: anywhere;
        }

        .wm-scout-supplier-card .wm-scout-card-title {
            flex: 0 1 auto;
            min-width: min(18rem, 100%);
        }

        .wm-scout-card-subtitle {
            margin: 0.2rem 0 0;
            color: #776f68;
            font-size: 0.86rem;
            line-height: 1.5;
        }

        .wm-scout-supplier-meta {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.3rem 0.7rem;
            color: #5f5953;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .wm-scout-supplier-meta-item {
            display: inline-flex;
            align-items: baseline;
            gap: 0.28rem;
            min-width: 0;
        }

        .wm-scout-supplier-meta-label {
            color: #968c82;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .wm-scout-supplier-meta-value {
            color: #2d2a26;
            font-weight: 700;
            overflow-wrap: anywhere;
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
            display: grid;
            gap: 0.45rem;
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
            line-height: 1.45;
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
            justify-content: flex-end;
            align-items: center;
        }

        .wm-scout-card-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: 0.55rem;
            margin-top: 0.25rem;
        }

        .wm-scout-inline-form {
            margin-top: 0.2rem;
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid #ddd2c5;
            background: rgba(255, 255, 255, 0.92);
            display: grid;
            gap: 0.85rem;
        }

        .wm-scout-form-section {
            padding: 0.95rem;
            border-radius: 0.95rem;
            border: 1px solid #ece5dd;
            background: #fff;
        }

        .wm-scout-form-section-title {
            margin: 0 0 0.75rem;
            color: #2e4a62;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
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

        .wm-scout-select {
            appearance: none;
            padding-right: 2.75rem;
            background-image:
                linear-gradient(45deg, transparent 50%, #746d66 50%),
                linear-gradient(135deg, #746d66 50%, transparent 50%);
            background-position:
                calc(100% - 1.18rem) 50%,
                calc(100% - 0.9rem) 50%;
            background-size: 0.32rem 0.32rem, 0.32rem 0.32rem;
            background-repeat: no-repeat;
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

        .wm-cost-items {
            display: grid;
            gap: 0.65rem;
        }

        .wm-cost-item-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(8rem, 0.35fr) auto;
            gap: 0.6rem;
            align-items: center;
        }

        .wm-cost-item-select {
            min-height: 3.05rem;
            border-color: #d8cabb;
            background-color: #fbf8f4;
            font-weight: 650;
        }

        .wm-cost-item-note {
            margin: 0.65rem 0 0;
            color: #746d66;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .wm-cost-remove {
            min-height: 2.9rem;
        }

        .wm-comparison-wrap {
            overflow-x: auto;
            border: 1px solid #c7d7bd;
            border-radius: 0.9rem;
            background: #e5efdd;
        }

        .wm-comparison-table {
            width: 100%;
            min-width: 48rem;
            border-collapse: collapse;
            color: #172014;
        }

        .wm-comparison-table th,
        .wm-comparison-table td {
            border: 1px solid #283625;
            padding: 0.45rem 0.55rem;
            text-align: center;
            vertical-align: middle;
        }

        .wm-comparison-table th {
            background: #c6ddb8;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.05rem;
        }

        .wm-comparison-table th:first-child,
        .wm-comparison-table td:first-child {
            text-align: left;
            min-width: 18rem;
        }

        .wm-comparison-total td {
            background: #c6ddb8;
            font-weight: 800;
            font-size: 1rem;
        }

        @media (max-width: 1280px) {
            .wm-event-top-head,
            .wm-scout-search-form,
            .wm-scout-kpis,
            .wm-scout-data-grid,
            .wm-scout-inline-grid,
            .wm-cost-item-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .wm-event-top-side,
            .wm-scout-card-head,
            .wm-scout-request-header {
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

            .wm-scout-actions,
            .wm-scout-card-actions {
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
                Search suppliers in this category, register sent requests, collect responses and mark accepted quotes.
                More than one supplier quote can be accepted for the same budget category.
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
                <div class="wm-scout-kpi is-primary">
                    <p class="wm-scout-kpi-label">Confirmed</p>
                    <p class="wm-scout-kpi-value">{{ $summary['final_amount'] !== null ? 'EUR ' . number_format($summary['final_amount'], 2, ',', '.') : '—' }}</p>
                </div>
                <div class="wm-scout-kpi">
                    <p class="wm-scout-kpi-label">Requests</p>
                    <p class="wm-scout-kpi-value">{{ $summary['proposal_count'] }} / {{ $summary['responses_count'] }} responses</p>
                </div>
                <div class="wm-scout-kpi">
                    <p class="wm-scout-kpi-label">Accepted quotes</p>
                    <p class="wm-scout-kpi-value">{{ $summary['confirmed_count'] }}</p>
                </div>
            </div>
        </section>

        <section class="wm-event-card wm-scout-request-list">
            <div class="wm-scout-section-head">
                <div>
                    <p class="wm-scout-section-kicker">Quote workflow</p>
                    <h3 class="wm-scout-section-title">Requests sent</h3>
                </div>
                <div class="wm-scout-section-actions">
                    @if ($canExportPresentationPdf && $presentationExportCount > 0)
                        <a
                            href="{{ route('admin.projects.budget.proposals.pdf', ['project' => $record, 'categoryBudget' => $budget]) }}"
                            class="wm-scout-export-button"
                            target="_blank"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path>
                                <path d="M14 2v6h6"></path>
                                <path d="M12 18v-6"></path>
                                <path d="m9 15 3 3 3-3"></path>
                            </svg>
                            Export presentation PDF
                        </a>
                    @endif
                    <span class="wm-scout-section-meta">{{ $requests->count() }} tracked suppliers</span>
                </div>
            </div>

            @if ($requests->isEmpty())
                <div class="wm-scout-empty">No supplier requests have been registered for this category yet.</div>
            @else
                <div class="wm-scout-request-grid">
                    @foreach ($requests as $proposal)
                        @php
                            $supplierName = $proposal->supplier?->name ?? 'Supplier';
                            $supplierArea = collect([$proposal->supplier?->service_area, $proposal->supplier?->city])->filter()->implode(' • ');
                            $isConfirmed = $proposal->proposal_status === \App\Models\CategoryBudgetSupplier::STATUS_CONFIRMED;
                            $availabilityBadgeClass = match ($proposal->availability_status) {
                                'available' => 'is-success',
                                'unavailable' => 'is-danger',
                                default => 'is-warning',
                            };
                        @endphp
                        <article class="wm-scout-request-card {{ $isConfirmed ? 'is-confirmed' : '' }}">
                            <div class="wm-scout-request-header">
                                <div class="wm-scout-request-identity">
                                    <span class="wm-scout-supplier-avatar" aria-hidden="true">
                                        {{ \Illuminate\Support\Str::of($supplierName)->trim()->substr(0, 1)->upper() }}
                                    </span>
                                    <div>
                                        <p class="wm-scout-supplier-eyebrow">Supplier</p>
                                        <h4 class="wm-scout-card-title">{{ $supplierName }}</h4>
                                    </div>
                                </div>

                                <div class="wm-scout-badges">
                                    <span class="wm-scout-badge {{ $availabilityBadgeClass }}">
                                        {{ \App\Models\CategoryBudgetSupplier::AVAILABILITY_STATUS_OPTIONS[$proposal->availability_status] ?? $proposal->availability_status }}
                                    </span>
                                    <span class="wm-scout-badge">
                                        {{ \App\Models\CategoryBudgetSupplier::SCOUTING_STATUS_OPTIONS[$proposal->scouting_status] ?? $proposal->scouting_status }}
                                    </span>
                                </div>
                            </div>

                            <div class="wm-scout-request-body">
                                @if (filled($supplierArea))
                                    <p class="wm-scout-card-subtitle">
                                        {{ $supplierArea }}
                                    </p>
                                @endif

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
                                    @if (collect($proposal->cost_items_json ?? [])->isNotEmpty())
                                        <div class="wm-scout-copy-block">
                                            <strong>Cost breakdown</strong>
                                            <p>
                                                @foreach ($proposal->cost_items_json ?? [] as $item)
                                                    {{ $item['label'] ?? '' }}: {{ $money($item['amount'] ?? null) }}{{ ! $loop->last ? "\n" : '' }}
                                                @endforeach
                                            </p>
                                        </div>
                                    @endif

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

                                <div class="wm-scout-card-actions">
                                    <x-filament::button
                                        color="gray"
                                        size="sm"
                                        icon="heroicon-m-pencil-square"
                                        wire:click="openRecordResponseModal({{ $proposal->id }})"
                                    >
                                        Update quote / response
                                    </x-filament::button>

                                    @if ($proposal->hasResponse() && ! $isConfirmed)
                                        <x-filament::button
                                            color="success"
                                            size="sm"
                                            icon="heroicon-m-check-circle"
                                            wire:click="openAcceptProposalModal({{ $proposal->id }})"
                                        >
                                            Mark accepted quote
                                        </x-filament::button>
                                    @elseif ($isConfirmed)
                                        <a
                                            href="{{ \App\Filament\Resources\ProjectResource::getUrl('supplier-manage', ['record' => $record, 'proposal' => $proposal->id]) }}"
                                            class="wm-scout-attachment"
                                        >
                                            Manage
                                        </a>
                                    @endif
                                </div>

                                @if ($responseFormContext === 'requests' && $responseProposalId === $proposal->id)
                                    @include('filament.resources.project-resource.pages.partials.budget-response-form', [
                                        'proposal' => $proposal,
                                        'responseFormKey' => 'proposal-' . $proposal->id,
                                        'saveLabel' => 'Save response',
                                    ])
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($comparison['enabled'])
            <section class="wm-event-card wm-scout-request-list">
                <div class="wm-scout-section-head">
                    <div>
                        <p class="wm-scout-section-kicker">Comparison</p>
                        <h3 class="wm-scout-section-title">Quote cost breakdown comparison</h3>
                    </div>
                    <div class="wm-scout-section-actions">
                        <a href="{{ $this->comparisonPdfUrl() }}" class="wm-scout-export-button" target="_blank">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path>
                                <path d="M14 2v6h6"></path>
                                <path d="M12 18v-6"></path>
                                <path d="m9 15 3 3 3-3"></path>
                            </svg>
                            Export comparison PDF
                        </a>
                    </div>
                </div>

                <div class="wm-comparison-wrap">
                    <table class="wm-comparison-table">
                        <thead>
                            <tr>
                                <th>{{ $summary['label'] }}</th>
                                @foreach ($comparison['proposals'] as $proposal)
                                    <th>{{ $proposal->supplier?->name ?? 'Supplier' }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comparison['rows'] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    @foreach ($comparison['proposals'] as $proposal)
                                        <td>{{ $money($row['amounts'][$proposal->id] ?? null) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="wm-comparison-total">
                                <td>Quote total</td>
                                @foreach ($comparison['proposals'] as $proposal)
                                    <td>{{ $money($comparison['totals'][$proposal->id] ?? null) }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="wm-event-card wm-scout-search">
            <div class="wm-scout-section-head">
                <div>
                    <p class="wm-scout-section-kicker">Supplier database</p>
                    <h3 class="wm-scout-section-title">Supplier search</h3>
                </div>
                <span class="wm-scout-section-meta">
                    @if ($budget->budget_status === \App\Models\CategoryBudget::STATUS_HYPOTHETICAL)
                        Start scouting
                    @else
                        Add more requests
                    @endif
                </span>
            </div>

            <div class="wm-scout-actions" style="margin-top: 0; margin-bottom: 1rem;">
                <x-filament::button color="gray" icon="heroicon-m-plus" wire:click="openCreateSupplierModal">
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

                <x-filament::button color="gray" icon="heroicon-m-arrow-path" wire:click="resetSupplierFilters">
                    Reset
                </x-filament::button>
            </div>

            @if (! $this->hasSupplierSearchFilters())
                <div class="wm-scout-empty">Start typing or choose a filter to show suppliers.</div>
            @elseif ($supplierResults->isEmpty())
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
                                    <div class="wm-scout-card-main">
                                        <h4 class="wm-scout-card-title">{{ $supplier->name }}</h4>
                                        <div class="wm-scout-supplier-meta" aria-label="Supplier details">
                                            <span class="wm-scout-supplier-meta-item">
                                                <span class="wm-scout-supplier-meta-label">Contact</span>
                                                <span class="wm-scout-supplier-meta-value">{{ $supplier->contact_person ?: '—' }}</span>
                                            </span>
                                            <span class="wm-scout-supplier-meta-item">
                                                <span class="wm-scout-supplier-meta-label">Email</span>
                                                <span class="wm-scout-supplier-meta-value">{{ $supplier->email ?: '—' }}</span>
                                            </span>
                                            <span class="wm-scout-supplier-meta-item">
                                                <span class="wm-scout-supplier-meta-label">Phone</span>
                                                <span class="wm-scout-supplier-meta-value">{{ $supplier->phone ?: '—' }}</span>
                                            </span>
                                            <span class="wm-scout-supplier-meta-item">
                                                <span class="wm-scout-supplier-meta-label">Price range</span>
                                                <span class="wm-scout-supplier-meta-value">{{ $supplier->price_range ?: '—' }}</span>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="wm-scout-card-subtitle">
                                        {{ collect([$supplier->service_area, $supplier->city, $supplier->province])->filter()->implode(' • ') ?: 'No area specified' }}
                                    </p>
                                </div>

                                @if ($existingProposal)
                                    <span class="wm-scout-badge is-warning">
                                        {{ \App\Models\CategoryBudgetSupplier::SCOUTING_STATUS_OPTIONS[$existingProposal->scouting_status] ?? 'Tracked' }}
                                    </span>
                                @endif
                            </div>

                            @if (filled($supplier->style_description))
                                <div class="wm-scout-copy">
                                    <div class="wm-scout-copy-block">
                                        <strong>Style</strong>
                                        <p>{{ $supplier->style_description }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="wm-scout-card-actions">
                                <x-filament::button
                                    color="{{ $existingProposal ? 'gray' : 'primary' }}"
                                    icon="{{ $existingProposal ? 'heroicon-m-pencil-square' : 'heroicon-m-paper-airplane' }}"
                                    wire:click="startSendRequest({{ $supplier->id }})"
                                >
                                    {{ $existingProposal ? 'Update request' : 'Request sent' }}
                                </x-filament::button>

                                <x-filament::button
                                    color="success"
                                    icon="heroicon-m-document-plus"
                                    wire:click="startInsertAcceptedQuote({{ $supplier->id }})"
                                >
                                    Insert quote
                                </x-filament::button>
                            </div>

                            @if ($requestSupplierId === $supplier->id)
                                <div class="wm-scout-inline-form">
                                    <div class="wm-scout-form-section">
                                        <p class="wm-scout-form-section-title">Request details</p>
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

                                        <div style="margin-top: 0.75rem;">
                                            <label class="wm-scout-label" for="request-text-{{ $supplier->id }}">Request text</label>
                                            <textarea
                                                id="request-text-{{ $supplier->id }}"
                                                class="wm-scout-textarea"
                                                wire:model="requestForm.request_text"
                                            ></textarea>
                                        </div>

                                        <div style="margin-top: 0.75rem;">
                                            <label class="wm-scout-label" for="planner-notes-{{ $supplier->id }}">Planner notes</label>
                                            <textarea
                                                id="planner-notes-{{ $supplier->id }}"
                                                class="wm-scout-textarea"
                                                wire:model="requestForm.planner_notes"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <div class="wm-scout-actions" style="margin-top: 0;">
                                        <x-filament::button icon="heroicon-m-check" wire:click="saveSendRequest">
                                            Save request
                                        </x-filament::button>

                                        <x-filament::button color="gray" icon="heroicon-m-x-mark" wire:click="cancelSendRequest">
                                            Cancel
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endif

                            @if ($responseFormContext === 'supplier' && (($existingProposal && $responseProposalId === $existingProposal->id) || $responseSupplierId === $supplier->id))
                                @include('filament.resources.project-resource.pages.partials.budget-response-form', [
                                    'responseFormKey' => 'supplier-' . $supplier->id,
                                    'saveLabel' => 'Save quote',
                                ])
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
