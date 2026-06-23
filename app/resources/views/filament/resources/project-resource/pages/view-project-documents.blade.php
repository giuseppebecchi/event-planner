<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $projectDocuments = $this->getProjectDocuments();
        $supplierDocumentGroups = $this->getSupplierDocumentGroups();
        $summary = $this->getDocumentsSummary();
    @endphp

    <style>
        .wm-documents-page { display: flex; flex-direction: column; gap: 1rem; }
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
        .wm-event-top-meta span { position: relative; }
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
        .wm-event-countdown-edit svg { width: 1rem; height: 1rem; }
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
        .wm-event-top-date-tools { width: 100%; }
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
        .wm-event-date-actions { display: flex; flex-wrap: wrap; gap: 0.7rem; }
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
        .wm-event-workspace::-webkit-scrollbar { display: none; }
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
        .wm-documents-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }
        .wm-doc-stat { padding: 1rem 1.1rem; }
        .wm-doc-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .wm-doc-stat-value {
            margin: 0.4rem 0 0;
            color: #2d2a26;
            font-size: 1.45rem;
            font-weight: 800;
        }
        .wm-documents-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }
        .wm-doc-panel { padding: 1rem; display: grid; gap: 0.9rem; }
        .wm-doc-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }
        .wm-doc-panel-title {
            margin: 0.2rem 0 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: 1.05rem;
        }
        .wm-doc-panel-copy {
            margin: 0.3rem 0 0;
            color: #746d66;
            font-size: 0.84rem;
            line-height: 1.5;
        }
        .wm-doc-feature-list,
        .wm-doc-list { display: grid; }
        .wm-doc-supplier-group {
            display: grid;
            gap: 0.55rem;
            padding: 0.95rem 0;
            border-top: 1px solid #e8e0d7;
        }
        .wm-doc-supplier-group:first-child { border-top: 0; padding-top: 0; }
        .wm-doc-supplier-group:last-child { padding-bottom: 0; }
        .wm-doc-supplier-head {
            display: grid;
            grid-template-columns: minmax(10rem, 0.85fr) minmax(0, 1.4fr) auto;
            gap: 0.8rem;
            align-items: center;
        }
        .wm-doc-supplier-title {
            margin: 0;
            color: #2d2a26;
            font-size: 1rem;
            font-weight: 900;
            line-height: 1.25;
        }
        .wm-doc-supplier-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 1.85rem;
            border-radius: 999px;
            padding: 0 0.65rem;
            background: rgba(46, 74, 98, 0.1);
            color: #2e4a62;
            font-size: 0.72rem;
            font-weight: 900;
            white-space: nowrap;
        }
        .wm-doc-supplier-cats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }
        .wm-doc-feature {
            display: grid;
            grid-template-columns: minmax(7rem, 0.45fr) minmax(0, 1fr) minmax(8.5rem, 0.45fr) auto;
            gap: 0.85rem;
            align-items: center;
            padding: 0.75rem 0;
            border-top: 1px solid rgba(201, 169, 106, 0.28);
        }
        .wm-doc-feature:first-child { border-top: 0; }
        .wm-doc-feature-list {
            border: 1px solid rgba(201, 169, 106, 0.38);
            border-radius: 0.75rem;
            background: rgba(201, 169, 106, 0.08);
            padding: 0 0.85rem;
        }
        .wm-doc-table { display: grid; }
        .wm-doc-table-head,
        .wm-doc-row {
            display: grid;
            grid-template-columns: minmax(7rem, 0.42fr) minmax(0, 1fr) minmax(8.5rem, 0.42fr) auto;
            gap: 0.85rem;
            align-items: center;
        }
        .wm-doc-table-head {
            min-height: 2rem;
            border-top: 1px solid #ebe2d8;
            border-bottom: 1px solid #ebe2d8;
            color: #8b847d;
            font-size: 0.66rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .wm-doc-row {
            min-height: 3.6rem;
            padding: 0.62rem 0;
            border-bottom: 1px solid #efe8df;
        }
        .wm-doc-row:last-child {
            border-bottom: 0;
        }
        .wm-doc-title {
            margin: 0;
            color: #2d2a26;
            font-size: 0.92rem;
            font-weight: 800;
            line-height: 1.35;
        }
        .wm-doc-meta,
        .wm-doc-description {
            margin: 0.16rem 0 0;
            color: #746d66;
            font-size: 0.78rem;
            line-height: 1.45;
        }
        .wm-doc-description { color: #5f5953; }
        .wm-doc-badges { display: flex; flex-wrap: wrap; gap: 0.4rem; }
        .wm-doc-badge {
            display: inline-flex;
            width: fit-content;
            border-radius: 999px;
            padding: 0.22rem 0.5rem;
            background: rgba(46, 74, 98, 0.1);
            color: #2e4a62;
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .wm-doc-badge.is-general {
            background: rgba(201, 169, 106, 0.16);
            color: #8b6423;
        }
        .wm-doc-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.1rem;
            border-radius: 999px;
            padding: 0 0.8rem;
            background: #2e4a62;
            color: #fff;
            font-size: 0.76rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            border: 0;
            cursor: pointer;
        }
        .wm-doc-action.is-secondary {
            background: #fbf8f4;
            color: #2e4a62;
            border: 1px solid #d8cec1;
        }
        .wm-doc-uploaded {
            color: #5f5953;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .wm-doc-empty {
            padding: 1rem;
            border: 1px dashed #d8cdc1;
            border-radius: 1rem;
            color: #8c857e;
            background: rgba(255, 255, 255, 0.55);
            font-size: 0.88rem;
        }
        @media (max-width: 1100px) {
            .wm-documents-summary,
            .wm-documents-layout,
            .wm-event-top-head,
            .wm-event-date-grid { grid-template-columns: 1fr; }
            .wm-event-top-side {
                flex-direction: column;
                align-items: stretch;
            }
            .wm-event-summary-chip,
            .wm-event-countdown { width: 100%; }
        }
        @media (max-width: 720px) {
            .wm-doc-feature,
            .wm-doc-row,
            .wm-doc-supplier-head { grid-template-columns: 1fr; }
            .wm-doc-table-head { display: none; }
            .wm-doc-action { width: fit-content; }
            .wm-doc-uploaded { white-space: normal; }
        }
    </style>

    <div class="wm-documents-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'documents',
        ])

        <section class="wm-documents-summary">
            <article class="wm-event-card wm-doc-stat">
                <p class="wm-doc-label">Total documents</p>
                <p class="wm-doc-stat-value">{{ $summary['total_count'] }}</p>
            </article>
            <article class="wm-event-card wm-doc-stat">
                <p class="wm-doc-label">Project documents</p>
                <p class="wm-doc-stat-value">{{ $summary['project_documents_count'] }}</p>
            </article>
            <article class="wm-event-card wm-doc-stat">
                <p class="wm-doc-label">Supplier documents</p>
                <p class="wm-doc-stat-value">{{ $summary['supplier_documents_count'] }}</p>
            </article>
            <article class="wm-event-card wm-doc-stat">
                <p class="wm-doc-label">Suppliers covered</p>
                <p class="wm-doc-stat-value">{{ $summary['suppliers_count'] }}</p>
            </article>
        </section>

        <section class="wm-documents-layout">
            <article class="wm-event-card wm-doc-panel">
                <div class="wm-doc-panel-head">
                    <div>
                        <p class="wm-doc-label">Highlighted</p>
                        <h3 class="wm-doc-panel-title">Project documents</h3>
                        <p class="wm-doc-panel-copy">General files such as contracts, signed agreements and project-level paperwork.</p>
                    </div>
                    @if (! auth()->user()?->isCustomer())
                        <button type="button" class="wm-doc-action is-secondary" wire:click="mountAction('addProjectDocument')">
                            Add document
                        </button>
                    @endif
                </div>

                <div class="wm-doc-feature-list">
                    @forelse ($projectDocuments as $document)
                        <div class="wm-doc-feature">
                            <div>
                                <span class="wm-doc-badge is-general">{{ $this->documentTypeLabel($document) }}</span>
                            </div>
                            <div>
                                <p class="wm-doc-title">{{ $document->title }}</p>
                                @if ($document->description)
                                    <p class="wm-doc-description">{{ $document->description }}</p>
                                @endif
                            </div>
                            <div class="wm-doc-uploaded">{{ $document->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                            <a class="wm-doc-action" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}" target="_blank" rel="noopener">
                                Open document
                            </a>
                        </div>
                    @empty
                        <div class="wm-doc-empty">No general project documents yet.</div>
                    @endforelse
                </div>
            </article>

            <article class="wm-event-card wm-doc-panel">
                <div class="wm-doc-panel-head">
                    <div>
                        <p class="wm-doc-label">Suppliers</p>
                        <h3 class="wm-doc-panel-title">Supplier documents</h3>
                        <p class="wm-doc-panel-copy">Quotes, contracts, invoices and payment receipts connected to confirmed suppliers.</p>
                    </div>
                </div>

                <div class="wm-doc-list">
                    @forelse ($supplierDocumentGroups as $group)
                        <section class="wm-doc-supplier-group">
                            <div class="wm-doc-supplier-head">
                                <h4 class="wm-doc-supplier-title">{{ $group['supplier_name'] }}</h4>
                                @if ($group['categories']->isNotEmpty())
                                    <div class="wm-doc-supplier-cats">
                                        @foreach ($group['categories'] as $category)
                                            <span class="wm-doc-badge">{{ $category }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <div></div>
                                @endif
                                <span class="wm-doc-supplier-count">{{ $group['documents']->count() }} {{ \Illuminate\Support\Str::plural('doc', $group['documents']->count()) }}</span>
                            </div>

                            <div class="wm-doc-table">
                                <div class="wm-doc-table-head">
                                    <span>Type</span>
                                    <span>Document</span>
                                    <span>Uploaded</span>
                                    <span></span>
                                </div>
                                @foreach ($group['documents'] as $document)
                                    <div class="wm-doc-row">
                                        <div>
                                            <span class="wm-doc-badge">{{ $this->documentTypeLabel($document) }}</span>
                                        </div>
                                        <div>
                                            <p class="wm-doc-title">{{ $document->title }}</p>
                                            @if ($document->description)
                                                <p class="wm-doc-description">{{ $document->description }}</p>
                                            @endif
                                        </div>
                                        <div class="wm-doc-uploaded">{{ $document->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                        <a class="wm-doc-action" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}" target="_blank" rel="noopener">
                                            Open
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="wm-doc-empty">No supplier documents yet.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
