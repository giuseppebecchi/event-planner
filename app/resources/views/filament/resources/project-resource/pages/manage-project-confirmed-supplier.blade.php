<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $summary = $this->getSummary();
        $dashboardCards = $this->getDashboardCards();
        $communications = $this->getCommunications();
        $quoteDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_QUOTE);
        $contractDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_CONTRACT);
        $signedContractDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_SIGNED_CONTRACT);
        $invoiceDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_INVOICE);
        $paymentReceiptDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_PAYMENT_RECEIPT);
        $otherDocuments = $this->getOtherDocuments();
        $payments = $this->getPayments();
        $images = $this->getImages();
        $checklistItems = $this->getChecklistItems();
    @endphp

    <style>
        .wm-supplier-manage-page {
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

        .wm-card {
            border: 1px solid var(--cup-border-soft, #e8e3dc);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 20px 42px rgba(45, 42, 38, 0.06);
        }

        .wm-panel {
            padding: 1.2rem 1.25rem;
        }

        .wm-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-link {
            color: #2e4a62;
            font-weight: 700;
            text-decoration: none;
        }

        .wm-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            color: #2d2a26;
            font-size: clamp(1.3rem, 1.9vw, 1.85rem);
        }

        .wm-copy {
            margin: 0.35rem 0 0;
            color: #746d66;
            line-height: 1.65;
        }

        .wm-quote-badge {
            min-width: 15rem;
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(240, 248, 242, 0.98), rgba(250, 252, 250, 0.98));
            border: 1px solid rgba(83, 168, 106, 0.18);
        }

        .wm-quote-badge-label {
            margin: 0;
            color: #6d8a73;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-quote-badge-value {
            margin: 0.45rem 0 0;
            color: #2d2a26;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .wm-quote-badge-meta {
            margin: 0.45rem 0 0;
            color: #746d66;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .wm-quote-badge-meta.is-positive {
            color: #2d7a39;
            font-weight: 700;
        }

        .wm-quote-badge-meta.is-negative {
            color: #b54c3d;
            font-weight: 700;
        }

        .wm-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-dashboard-card {
            position: relative;
            display: grid;
            gap: 0.55rem;
            width: 100%;
            padding: 1.05rem 1.1rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(251, 248, 244, 0.95), rgba(255, 255, 255, 0.98));
            border: 1px solid #ece5dd;
            text-align: left;
            cursor: pointer;
        }

        .wm-dashboard-card:hover,
        .wm-dashboard-card.is-active {
            border-color: rgba(201, 169, 106, 0.42);
            background: linear-gradient(180deg, rgba(247, 243, 237, 0.98), rgba(255, 255, 255, 0.98));
        }

        .wm-dashboard-card.is-active {
            border-color: rgba(83, 168, 106, 0.36);
            box-shadow: 0 18px 34px rgba(83, 168, 106, 0.14);
        }

        .wm-dashboard-card.is-active::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.32rem;
            border-radius: 1rem 0 0 1rem;
            background: linear-gradient(180deg, #53a86a 0%, #2d7a39 100%);
        }

        .wm-dashboard-card.is-active .wm-dashboard-label {
            color: #4f7a57;
        }

        .wm-dashboard-card.is-active .wm-dashboard-value {
            color: #2d7a39;
        }

        .wm-dashboard-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-dashboard-value {
            margin: 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: 1.45rem;
            line-height: 1.05;
        }

        .wm-dashboard-meta {
            margin: 0;
            color: #746d66;
            line-height: 1.55;
        }

        .wm-section {
            scroll-margin-top: 7rem;
        }

        .wm-section-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .wm-section-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            color: #2d2a26;
            font-size: 1.08rem;
        }

        .wm-section-subtitle {
            margin: 0.35rem 0 0;
            color: #746d66;
            line-height: 1.65;
        }

        .wm-two-col {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(22rem, 0.9fr);
            gap: 1rem;
        }

        .wm-three-col {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-form,
        .wm-list {
            display: grid;
            gap: 0.85rem;
        }

        .wm-inline-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .wm-label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-field,
        .wm-select,
        .wm-textarea {
            width: 100%;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0.8rem 0.95rem;
            color: #2d2a26;
        }

        .wm-textarea {
            min-height: 7rem;
            resize: vertical;
        }

        .wm-item {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-item-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-item-title {
            margin: 0;
            color: #2d2a26;
            font-weight: 700;
        }

        .wm-item-meta {
            margin: 0.2rem 0 0;
            color: #7b746e;
            font-size: 0.85rem;
        }

        .wm-item-copy {
            margin: 0.65rem 0 0;
            color: #605953;
            line-height: 1.65;
            white-space: pre-line;
        }

        .wm-badge {
            display: inline-flex;
            align-items: center;
            min-height: 1.8rem;
            padding: 0 0.75rem;
            border-radius: 999px;
            background: rgba(46, 74, 98, 0.08);
            color: #2e4a62;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 0.85rem;
        }

        .wm-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }

        .wm-radio-option {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.85rem 1rem;
            border-radius: 1rem;
            border: 1px solid #ece5dd;
            background: #fbf8f4;
            color: #4d473f;
            font-weight: 600;
        }

        .wm-radio-option input {
            margin: 0;
        }

        .wm-inline-form {
            margin-top: 0.9rem;
            padding-top: 0.9rem;
            border-top: 1px dashed #ddd2c5;
        }

        .wm-empty {
            padding: 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px dashed #ddd2c5;
            color: #7b746e;
        }

        .wm-doc-groups {
            display: grid;
            gap: 1rem;
        }

        .wm-doc-group {
            display: grid;
            gap: 0.85rem;
        }

        .wm-doc-group-title {
            margin: 0;
            color: #2d2a26;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .wm-gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .wm-gallery-card {
            overflow: hidden;
            padding: 0.7rem;
            border-radius: 1.15rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-gallery-card img {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            border-radius: 0.95rem;
        }

        .wm-gallery-copy {
            padding: 0.8rem 0.2rem 0.1rem;
        }

        .wm-checklist-grid {
            display: grid;
            gap: 0.85rem;
        }

        .wm-checklist-item {
            padding: 1rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(251, 248, 244, 0.96), rgba(255, 255, 255, 0.98));
            border: 1px solid #ece5dd;
        }

        @media (max-width: 1100px) {
            .wm-top-kpis,
            .wm-dashboard-grid,
            .wm-two-col,
            .wm-three-col,
            .wm-inline-grid,
            .wm-gallery-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="wm-supplier-manage-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'budget',
        ])

        <section class="wm-card wm-panel">
            <div class="wm-head">
                <div>
                    <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('budget', ['record' => $record]) }}" class="wm-link">← Back to budget recap</a>
                    <h2 class="wm-title" style="margin-top:.45rem;">{{ $summary['supplier'] }}</h2>
                    <p class="wm-copy">{{ $summary['category'] }} · confirmed supplier workspace for this project.</p>
                </div>
                <div class="wm-quote-badge">
                    <p class="wm-quote-badge-label">Confirmed quote</p>
                    <p class="wm-quote-badge-value">{{ $summary['confirmed_amount'] !== null ? 'EUR ' . number_format($summary['confirmed_amount'], 2, ',', '.') : '—' }}</p>
                    @if ($summary['amount_delta'] !== null && $summary['amount_delta'] !== 0.0)
                        <p class="wm-quote-badge-meta {{ $summary['amount_delta'] < 0 ? 'is-positive' : 'is-negative' }}">
                            {{ $summary['amount_delta'] < 0 ? 'Savings' : 'Over budget' }}
                            {{ 'EUR ' . number_format(abs($summary['amount_delta']), 2, ',', '.') }}
                        </p>
                    @elseif ($summary['amount_delta'] !== null)
                        <p class="wm-quote-badge-meta">Aligned with the initial estimate</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="wm-dashboard-grid">
            @foreach ($dashboardCards as $card)
                <button
                    type="button"
                    wire:click="setActiveWorkspaceTab('{{ $card['key'] }}')"
                    class="wm-dashboard-card wm-card {{ $this->activeWorkspaceTab === $card['key'] ? 'is-active' : '' }}"
                >
                    <p class="wm-dashboard-label">{{ $card['label'] }}</p>
                    <p class="wm-dashboard-value">{{ $card['value'] }}</p>
                    <p class="wm-dashboard-meta">{{ $card['meta'] }}</p>
                </button>
            @endforeach
        </section>

        @if ($this->activeWorkspaceTab === 'communications')
        <section id="communications" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Communications</h3>
                        <p class="wm-section-subtitle">Timeline of quote requests, responses and every follow-up communication with this supplier.</p>
                    </div>
                </div>

                <div class="wm-list">
                    @forelse ($communications as $communication)
                        <div class="wm-item">
                            <div class="wm-item-head">
                                <div>
                                    <p class="wm-item-title">{{ $communication->subject ?: (\App\Models\ProjectSupplierCommunication::TYPE_OPTIONS[$communication->communication_type] ?? $communication->communication_type) }}</p>
                                    <p class="wm-item-meta">
                                        {{ \App\Models\ProjectSupplierCommunication::TYPE_OPTIONS[$communication->communication_type] ?? $communication->communication_type }}
                                        · {{ \App\Models\ProjectSupplierCommunication::DIRECTION_OPTIONS[$communication->direction] ?? $communication->direction }}
                                        · {{ $communication->communication_at?->format('d/m/Y H:i') ?? '—' }}
                                    </p>
                                </div>
                                <span class="wm-badge">{{ \App\Models\ProjectSupplierCommunication::DIRECTION_OPTIONS[$communication->direction] ?? $communication->direction }}</span>
                            </div>
                            @if ($communication->message)
                                <p class="wm-item-copy">{{ $communication->message }}</p>
                            @endif
                            @if ($communication->notes)
                                <p class="wm-item-meta" style="margin-top:.55rem;">Notes: {{ $communication->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="wm-empty">No communication registered yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Add communication</h3>
                        <p class="wm-section-subtitle">Log emails, calls, meetings, site visits and any later update.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="communication-type">Communication type</label>
                            <select id="communication-type" class="wm-select" wire:model="communicationForm.communication_type">
                                @foreach (\App\Models\ProjectSupplierCommunication::TYPE_OPTIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="wm-label" for="communication-direction">Direction</label>
                            <select id="communication-direction" class="wm-select" wire:model="communicationForm.direction">
                                @foreach (\App\Models\ProjectSupplierCommunication::DIRECTION_OPTIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="communication-at">Date and time</label>
                            <input id="communication-at" type="datetime-local" class="wm-field" wire:model="communicationForm.communication_at">
                        </div>
                        <div>
                            <label class="wm-label" for="communication-subject">Subject</label>
                            <input id="communication-subject" type="text" class="wm-field" wire:model="communicationForm.subject">
                        </div>
                    </div>

                    <div>
                        <label class="wm-label" for="communication-message">Message</label>
                        <textarea id="communication-message" class="wm-textarea" wire:model="communicationForm.message"></textarea>
                    </div>

                    <div>
                        <label class="wm-label" for="communication-notes">Notes</label>
                        <textarea id="communication-notes" class="wm-textarea" wire:model="communicationForm.notes"></textarea>
                    </div>

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="saveCommunication">Save communication</x-filament::button>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if ($this->activeWorkspaceTab === 'documents')
        <section id="documents" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Documents</h3>
                        <p class="wm-section-subtitle">Quote files, contracts, invoices and receipts are stored here with upload date always visible.</p>
                    </div>
                </div>

                <div class="wm-doc-groups">
                    @php
                        $documentGroups = [
                            'Quote' => $quoteDocuments,
                            'Contracts' => $contractDocuments,
                            'Signed contracts' => $signedContractDocuments,
                            'Invoices' => $invoiceDocuments,
                            'Payment receipts' => $paymentReceiptDocuments,
                            'Other documents' => $otherDocuments,
                        ];
                    @endphp

                    @foreach ($documentGroups as $groupLabel => $documents)
                        <div class="wm-doc-group">
                            <h4 class="wm-doc-group-title">{{ $groupLabel }}</h4>
                            @forelse ($documents as $document)
                                <div class="wm-item">
                                    <div class="wm-item-head">
                                        <div>
                                            <p class="wm-item-title">{{ $document->title }}</p>
                                            <p class="wm-item-meta">
                                                {{ \App\Models\ProjectDocument::TYPE_OPTIONS[$document->type] ?? $document->type }}
                                                · uploaded {{ $document->created_at?->format('d/m/Y H:i') ?? '—' }}
                                            </p>
                                        </div>
                                    </div>
                                    @if ($document->description)
                                        <p class="wm-item-copy">{{ $document->description }}</p>
                                    @endif
                                    <div class="wm-actions">
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}" target="_blank" class="wm-link">Open</a>
                                        <button type="button" class="wm-link" wire:click="deleteDocument({{ $document->id }})">Delete</button>
                                    </div>
                                </div>
                            @empty
                                <div class="wm-empty">No {{ strtolower($groupLabel) }} yet.</div>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Upload document</h3>
                        <p class="wm-section-subtitle">Accepted quote documents already appear here as type <code>Quote</code>. Add the remaining files from this panel.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="document-type">Document type</label>
                            <select id="document-type" class="wm-select" wire:model="documentForm.type">
                                @foreach (\App\Models\ProjectDocument::TYPE_OPTIONS as $value => $label)
                                    @if ($value !== \App\Models\ProjectDocument::TYPE_PAYMENT_RECEIPT)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="wm-label" for="document-title">Title</label>
                            <input id="document-title" type="text" class="wm-field" wire:model="documentForm.title">
                        </div>
                    </div>

                    <div>
                        <label class="wm-label" for="document-description">Description</label>
                        <textarea id="document-description" class="wm-textarea" wire:model="documentForm.description"></textarea>
                    </div>

                    <div>
                        <label class="wm-label" for="document-upload">File</label>
                        <input id="document-upload" type="file" class="wm-field" wire:model="documentUpload">
                    </div>

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="saveDocument">Save document</x-filament::button>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if ($this->activeWorkspaceTab === 'photogallery')
        <section id="photogallery" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Photogallery</h3>
                        <p class="wm-section-subtitle">Visual gallery for dashboard use, client-facing selections and internal inspiration.</p>
                    </div>
                </div>

                <div class="wm-gallery-grid">
                    @forelse ($images as $image)
                        <div class="wm-gallery-card">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" alt="{{ $image->description ?: 'Project image' }}">
                            <div class="wm-gallery-copy">
                                <p class="wm-item-title">{{ \App\Models\ProjectImage::CATEGORY_OPTIONS[$image->image_category] ?? $image->image_category }}</p>
                                <p class="wm-item-meta">
                                    {{ $image->is_client_visible ? 'Visible to client' : 'Internal only' }}
                                    · uploaded {{ $image->created_at?->format('d/m/Y') ?? '—' }}
                                </p>
                                <p class="wm-item-copy" style="margin-top:.45rem;">{{ $image->description ?: 'No description' }}</p>
                                <div class="wm-actions">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" target="_blank" class="wm-link">Open</a>
                                    <button type="button" class="wm-link" wire:click="deleteImage({{ $image->id }})">Delete</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="wm-empty">No images uploaded yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Add image</h3>
                        <p class="wm-section-subtitle">Keep the gallery curated with category, note and visibility for client presentations.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div>
                        <label class="wm-label" for="image-upload">Image</label>
                        <input id="image-upload" type="file" class="wm-field" wire:model="imageUpload">
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="image-category">Image category</label>
                            <select id="image-category" class="wm-select" wire:model="imageForm.image_category">
                                @foreach (\App\Models\ProjectImage::CATEGORY_OPTIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display:flex;align-items:end;">
                            <label style="display:inline-flex;gap:.6rem;align-items:center;color:#4d473f;font-weight:600;">
                                <input type="checkbox" wire:model="imageForm.is_client_visible">
                                <span>Visible in client presentations</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="wm-label" for="image-description">Description</label>
                        <textarea id="image-description" class="wm-textarea" wire:model="imageForm.description"></textarea>
                    </div>

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="saveImage">Save image</x-filament::button>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if ($this->activeWorkspaceTab === 'payments')
        <section id="payments" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Payments</h3>
                        <p class="wm-section-subtitle">Track deposits, balances, invoice references and receipts tied to this supplier.</p>
                    </div>
                </div>

                <div class="wm-list">
                    @forelse ($payments as $payment)
                        <div class="wm-item">
                            <div class="wm-item-head">
                                <div>
                                    <p class="wm-item-title">{{ $payment->reason }}</p>
                                    <p class="wm-item-meta">
                                        EUR {{ number_format((float) $payment->amount, 2, ',', '.') }}
                                        · due {{ $payment->due_date?->format('d/m/Y') ?? '—' }}
                                        · {{ \App\Models\Payment::STATUS_OPTIONS[$payment->payment_status] ?? $payment->payment_status }}
                                        @if ($payment->paid_at)
                                            · paid {{ $payment->paid_at->format('d/m/Y') }}
                                        @endif
                                        @if ($payment->invoice_reference)
                                            · invoice {{ $payment->invoice_reference }}
                                        @endif
                                    </p>
                                </div>
                                <span class="wm-badge">{{ \App\Models\Payment::STATUS_OPTIONS[$payment->payment_status] ?? $payment->payment_status }}</span>
                            </div>
                            @if ($payment->notes)
                                <p class="wm-item-copy">{{ $payment->notes }}</p>
                            @endif
                            <div class="wm-actions">
                                @if ($payment->paymentReceiptDocument)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->paymentReceiptDocument->file_path) }}" target="_blank" class="wm-link">Open receipt</a>
                                @endif
                                @if ($payment->payment_status === \App\Models\Payment::STATUS_UNPAID)
                                    <button type="button" class="wm-link" wire:click="startPaymentRegistration({{ $payment->id }})">Register payment</button>
                                @endif
                                <button type="button" class="wm-link" wire:click="deletePayment({{ $payment->id }})">Delete</button>
                            </div>

                            @if (($this->openPaymentRegistrations[$payment->id] ?? false) && $payment->payment_status === \App\Models\Payment::STATUS_UNPAID)
                                <div class="wm-inline-form">
                                    <div class="wm-inline-grid">
                                        <div>
                                            <label class="wm-label" for="register-payment-paid-at-{{ $payment->id }}">Payment date</label>
                                            <input id="register-payment-paid-at-{{ $payment->id }}" type="date" class="wm-field" wire:model="paymentCompletionForms.{{ $payment->id }}.paid_at">
                                        </div>
                                        <div>
                                            <label class="wm-label" for="register-payment-receipt-{{ $payment->id }}">Payment receipt</label>
                                            <input id="register-payment-receipt-{{ $payment->id }}" type="file" class="wm-field" wire:model="paymentCompletionReceiptUploads.{{ $payment->id }}">
                                        </div>
                                    </div>

                                    <div class="wm-actions">
                                        <x-filament::button color="primary" wire:click="registerScheduledPayment({{ $payment->id }})">Confirm payment</x-filament::button>
                                        <button type="button" class="wm-link" wire:click="cancelPaymentRegistration({{ $payment->id }})">Cancel</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="wm-empty">No payments recorded yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Add payment</h3>
                        <p class="wm-section-subtitle">Register a completed payment or schedule an upcoming one for this supplier.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div>
                        <label class="wm-label">Payment mode</label>
                        <div class="wm-radio-group">
                            <label class="wm-radio-option">
                                <input type="radio" wire:model.live="paymentEntryMode" value="register">
                                <span>Register payment</span>
                            </label>
                            <label class="wm-radio-option">
                                <input type="radio" wire:model.live="paymentEntryMode" value="schedule">
                                <span>Schedule payment</span>
                            </label>
                        </div>
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="payment-reason">Reason</label>
                            <input id="payment-reason" type="text" class="wm-field" wire:model="paymentForm.reason">
                        </div>
                        <div>
                            <label class="wm-label" for="payment-amount">Amount</label>
                            <input id="payment-amount" type="number" step="0.01" class="wm-field" wire:model="paymentForm.amount">
                        </div>
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="payment-due-date">Due date</label>
                            <input id="payment-due-date" type="date" class="wm-field" wire:model="paymentForm.due_date">
                        </div>
                        <div>
                            <label class="wm-label" for="payment-invoice-reference">Invoice reference</label>
                            <input id="payment-invoice-reference" type="text" class="wm-field" wire:model="paymentForm.invoice_reference">
                        </div>
                    </div>

                    @if ($this->paymentEntryMode === 'register')
                        <div class="wm-inline-grid">
                            <div>
                                <label class="wm-label" for="payment-paid-at">Payment date</label>
                                <input id="payment-paid-at" type="date" class="wm-field" wire:model="paymentForm.paid_at">
                            </div>
                        </div>

                        <div>
                            <label class="wm-label" for="payment-receipt">Payment receipt</label>
                            <input id="payment-receipt" type="file" class="wm-field" wire:model="paymentReceiptUpload">
                        </div>
                    @endif

                    <div>
                        <label class="wm-label" for="payment-notes">Notes</label>
                        <textarea id="payment-notes" class="wm-textarea" wire:model="paymentForm.notes"></textarea>
                    </div>

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="savePayment">{{ $this->paymentEntryMode === 'register' ? 'Register payment' : 'Schedule payment' }}</x-filament::button>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if ($this->activeWorkspaceTab === 'checklist')
        <section id="checklist" class="wm-section wm-card wm-panel">
            <div class="wm-section-head">
                <div>
                    <h3 class="wm-section-title">Checklist</h3>
                    <p class="wm-section-subtitle">Operational reminders and future milestone placeholders for this supplier.</p>
                </div>
            </div>

            <div class="wm-checklist-grid">
                @foreach ($checklistItems as $item)
                    <div class="wm-checklist-item">
                        <p class="wm-item-title">{{ $item['title'] }}</p>
                        <p class="wm-item-copy">{{ $item['detail'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
        @endif
    </div>
</x-filament-panels::page>
