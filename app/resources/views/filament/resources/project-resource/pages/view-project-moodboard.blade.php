<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $supplierBoards = $this->getSupplierBoards();
        $customBoards = $this->getCustomBoards();
    @endphp

    <style>
        .wm-moodboard-page {
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
        }

        .wm-event-workspace-link.is-active {
            background: rgba(122, 143, 123, 0.14);
            color: #2d7a39;
        }

        .wm-event-workspace-link.is-disabled {
            color: #b4aca3;
            background: rgba(246, 241, 235, 0.78);
            pointer-events: none;
            box-shadow: inset 0 0 0 1px rgba(226, 218, 209, 0.92);
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

        .wm-moodboard-shell {
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-moodboard-hero,
        .wm-moodboard-panel,
        .wm-moodboard-board,
        .wm-moodboard-empty {
            border: 1px solid #e9e0d4;
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 24px 48px rgba(38, 31, 25, 0.06);
        }

        .wm-moodboard-hero {
            position: relative;
            overflow: hidden;
            padding: 1.4rem 1.5rem 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(214, 184, 147, 0.2), transparent 28%),
                linear-gradient(135deg, rgba(252, 247, 241, 0.98), rgba(247, 241, 233, 0.98));
        }

        .wm-moodboard-hero::after {
            content: "";
            position: absolute;
            inset: auto -3rem -3rem auto;
            width: 12rem;
            height: 12rem;
            border-radius: 999px;
            background: rgba(214, 184, 147, 0.12);
            filter: blur(6px);
        }

        .wm-moodboard-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: end;
        }

        .wm-moodboard-kicker {
            margin: 0;
            color: #aa8960;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .wm-moodboard-title {
            margin: 0.35rem 0 0;
            color: #26211b;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.6rem, 3vw, 2.55rem);
            line-height: 1.06;
        }

        .wm-moodboard-copy {
            max-width: 44rem;
            margin: 0.65rem 0 0;
            color: #756c64;
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .wm-moodboard-hero-stats {
            display: inline-flex;
            gap: 0.65rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .wm-moodboard-stat {
            min-width: 7rem;
            padding: 0.85rem 0.95rem;
            border-radius: 1.15rem;
            border: 1px solid rgba(170, 137, 96, 0.18);
            background: rgba(255, 255, 255, 0.82);
        }

        .wm-moodboard-stat-label {
            margin: 0;
            color: #9b8e83;
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-moodboard-stat-value {
            margin: 0.28rem 0 0;
            color: #2d261f;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .wm-moodboard-panel {
            padding: 1.15rem 1.2rem;
        }

        .wm-moodboard-panel-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .wm-moodboard-panel-title {
            margin: 0;
            color: #28221c;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-moodboard-panel-copy {
            margin: 0.3rem 0 0;
            color: #796f66;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .wm-moodboard-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .wm-moodboard-pill,
        .wm-moodboard-link,
        .wm-moodboard-icon-button {
            border: 0;
            cursor: pointer;
        }

        .wm-moodboard-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 2.65rem;
            padding: 0 1rem;
            border-radius: 999px;
            background: linear-gradient(180deg, #fffdfa 0%, #f7f0e7 100%);
            color: #6d5834;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            box-shadow: inset 0 0 0 1px rgba(175, 144, 97, 0.24);
        }

        .wm-moodboard-pill svg,
        .wm-moodboard-link svg,
        .wm-moodboard-icon-button svg {
            width: 1rem;
            height: 1rem;
        }

        .wm-moodboard-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-moodboard-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-moodboard-board {
            overflow: hidden;
        }

        .wm-moodboard-board-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1rem 0.8rem;
            border-top: 4px solid var(--board-accent, #ab8a61);
        }

        .wm-moodboard-board-title {
            margin: 0;
            color: #2e2822;
            font-size: 1.08rem;
            font-weight: 700;
        }

        .wm-moodboard-board-subtitle {
            margin: 0.28rem 0 0;
            color: #83786d;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .wm-moodboard-board-meta {
            display: inline-flex;
            gap: 0.45rem;
            flex-wrap: wrap;
            margin-top: 0.55rem;
        }

        .wm-moodboard-tag {
            display: inline-flex;
            align-items: center;
            min-height: 1.75rem;
            padding: 0 0.68rem;
            border-radius: 999px;
            background: #f5eee7;
            color: #6c6156;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .wm-moodboard-board-actions {
            display: inline-flex;
            gap: 0.45rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .wm-moodboard-link,
        .wm-moodboard-icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.2rem;
            min-width: 2.2rem;
            padding: 0 0.85rem;
            border-radius: 999px;
            background: #f5eee7;
            color: #6a5c4d;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-moodboard-icon-button.is-danger {
            color: #a45d5d;
        }

        .wm-moodboard-masonry {
            column-count: 3;
            column-gap: 0.8rem;
            padding: 0 1rem 1rem;
        }

        .wm-moodboard-card {
            break-inside: avoid;
            margin-bottom: 0.8rem;
            border-radius: 1.05rem;
            overflow: hidden;
            background: #f3ece4;
        }

        .wm-moodboard-card-image {
            width: 100%;
            display: block;
        }

        .wm-moodboard-card-body {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 0.82rem 0.82rem;
            background: #fff;
        }

        .wm-moodboard-card-copy {
            min-width: 0;
        }

        .wm-moodboard-card-title {
            margin: 0;
            color: #322a23;
            font-size: 0.84rem;
            font-weight: 700;
            line-height: 1.5;
        }

        .wm-moodboard-card-meta {
            margin: 0.22rem 0 0;
            color: #8b7f73;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .wm-moodboard-card-delete {
            flex: 0 0 auto;
            width: 1.9rem;
            height: 1.9rem;
            border-radius: 999px;
            background: #f7ede9;
            color: #ad6760;
        }

        .wm-moodboard-empty {
            padding: 1.2rem 1.25rem;
            color: #887d74;
            line-height: 1.65;
        }

        .wm-moodboard-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(31, 24, 19, 0.34);
        }

        .wm-moodboard-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 50;
            width: min(36rem, calc(100vw - 2rem));
            max-height: calc(100vh - 2rem);
            overflow: auto;
            transform: translate(-50%, -50%);
            border-radius: 1.4rem;
            border: 1px solid #e0d5c7;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 26px 64px rgba(28, 22, 17, 0.18);
            padding: 1.2rem;
        }

        .wm-moodboard-modal-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .wm-moodboard-modal-title {
            margin: 0;
            color: #2b241d;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .wm-moodboard-modal-copy {
            margin: 0.35rem 0 0;
            color: #7c7268;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .wm-moodboard-field + .wm-moodboard-field {
            margin-top: 0.85rem;
        }

        .wm-moodboard-field label {
            display: block;
            margin-bottom: 0.36rem;
            color: #675c52;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-moodboard-input,
        .wm-moodboard-textarea,
        .wm-moodboard-select {
            width: 100%;
            min-height: 2.8rem;
            border: 1px solid #ddd1c3;
            border-radius: 0.95rem;
            padding: 0.8rem 0.95rem;
            background: #fff;
            color: #2e2822;
        }

        .wm-moodboard-textarea {
            min-height: 6rem;
            resize: vertical;
        }

        .wm-moodboard-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: #5d5348;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .wm-moodboard-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr));
            gap: 0.75rem;
            margin-top: 0.85rem;
        }

        .wm-moodboard-preview-item {
            border-radius: 1rem;
            overflow: hidden;
            background: #f3ece4;
            aspect-ratio: 1 / 1;
        }

        .wm-moodboard-preview img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .wm-moodboard-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        @media (max-width: 960px) {
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

            .wm-moodboard-hero-grid,
            .wm-moodboard-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .wm-moodboard-hero-stats {
                justify-content: start;
            }

            .wm-moodboard-masonry {
                column-count: 2;
            }
        }

        @media (max-width: 720px) {
            .wm-event-date-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .wm-moodboard-masonry {
                column-count: 1;
            }

            .wm-moodboard-panel-head,
            .wm-moodboard-board-head {
                align-items: start;
                flex-direction: column;
            }
        }
    </style>

    <div class="wm-moodboard-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'moodboard',
        ])

        <div class="wm-moodboard-shell">
            <section class="wm-moodboard-hero">
                <div class="wm-moodboard-hero-grid">
                    <div>
                        <p class="wm-moodboard-kicker">Visual direction</p>
                        <h1 class="wm-moodboard-title">Event Moodboard</h1>
                        <p class="wm-moodboard-copy">Collect supplier references and build dedicated inspiration boards for beauty, florals, tablescape and every visual layer of the event. Everything stays grouped by board, so the creative direction is readable at a glance.</p>
                    </div>

                    <div class="wm-moodboard-hero-stats">
                        <div class="wm-moodboard-stat">
                            <p class="wm-moodboard-stat-label">Supplier boards</p>
                            <p class="wm-moodboard-stat-value">{{ $supplierBoards->count() }}</p>
                        </div>
                        <div class="wm-moodboard-stat">
                            <p class="wm-moodboard-stat-label">Custom boards</p>
                            <p class="wm-moodboard-stat-value">{{ $customBoards->count() }}</p>
                        </div>
                        <div class="wm-moodboard-stat">
                            <p class="wm-moodboard-stat-label">Images</p>
                            <p class="wm-moodboard-stat-value">{{ $record->projectImages()->count() }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="wm-moodboard-panel">
                <div class="wm-moodboard-panel-head">
                    <div>
                        <h2 class="wm-moodboard-panel-title">Create A New Board</h2>
                        <p class="wm-moodboard-panel-copy">Start from common wedding boards or create a fully custom one for styling, stationery, lighting or any other visual stream.</p>
                    </div>

                    <div class="wm-moodboard-actions">
                        <button type="button" class="wm-moodboard-pill" wire:click="openBoardModal('Makeup')">
                            <x-heroicon-o-sparkles />
                            <span>Makeup</span>
                        </button>
                        <button type="button" class="wm-moodboard-pill" wire:click="openBoardModal('Florals')">
                            <x-heroicon-o-swatch />
                            <span>Florals</span>
                        </button>
                        <button type="button" class="wm-moodboard-pill" wire:click="openBoardModal('Tablescape')">
                            <x-heroicon-o-squares-2x2 />
                            <span>Tablescape</span>
                        </button>
                        <button type="button" class="wm-moodboard-pill" wire:click="openBoardModal">
                            <x-heroicon-o-plus />
                            <span>Custom board</span>
                        </button>
                    </div>
                </div>
            </section>

            <section class="wm-moodboard-section">
                <div class="wm-moodboard-panel-head">
                    <div>
                        <h2 class="wm-moodboard-panel-title">Supplier Boards</h2>
                        <p class="wm-moodboard-panel-copy">These references already come from supplier-related materials saved inside the project.</p>
                    </div>
                </div>

                @if ($supplierBoards->isEmpty())
                    <div class="wm-moodboard-empty">No supplier moodboards yet. As soon as images are uploaded from supplier management, they will appear here automatically.</div>
                @else
                    <div class="wm-moodboard-grid">
                        @foreach ($supplierBoards as $board)
                            <article class="wm-moodboard-board" style="--board-accent: {{ $board['accent'] }}">
                                <div class="wm-moodboard-board-head">
                                    <div>
                                        <h3 class="wm-moodboard-board-title">{{ $board['title'] }}</h3>
                                        <p class="wm-moodboard-board-subtitle">{{ $board['subtitle'] }}</p>
                                        <div class="wm-moodboard-board-meta">
                                            <span class="wm-moodboard-tag">{{ $board['images']->count() }} images</span>
                                            <span class="wm-moodboard-tag">Supplier linked</span>
                                        </div>
                                    </div>

                                    <div class="wm-moodboard-board-actions">
                                        <button type="button" class="wm-moodboard-link" wire:click="openImageModal('supplier', {{ $board['id'] }})">
                                            <x-heroicon-o-photo />
                                            <span>Add image</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="wm-moodboard-masonry">
                                    @foreach ($board['images'] as $image)
                                        <article class="wm-moodboard-card">
                                            <img class="wm-moodboard-card-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" alt="Moodboard image">
                                            <div class="wm-moodboard-card-body">
                                                <div class="wm-moodboard-card-copy">
                                                    @if ($image->description)
                                                        <p class="wm-moodboard-card-title">{{ $image->description }}</p>
                                                    @endif
                                                    <p class="wm-moodboard-card-meta">{{ $image->image_category }}</p>
                                                </div>
                                                <button type="button" class="wm-moodboard-icon-button wm-moodboard-card-delete" wire:click="promptDeleteImage({{ $image->id }})">
                                                    <x-heroicon-o-trash />
                                                </button>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="wm-moodboard-section">
                <div class="wm-moodboard-panel-head">
                    <div>
                        <h2 class="wm-moodboard-panel-title">Custom Boards</h2>
                        <p class="wm-moodboard-panel-copy">Curate visual territories that go beyond suppliers: makeup, florals, table styling, stationery and more.</p>
                    </div>
                </div>

                @if ($customBoards->isEmpty())
                    <div class="wm-moodboard-empty">No custom moodboards yet. Create one from the buttons above to start curating the event aesthetic.</div>
                @else
                    <div class="wm-moodboard-grid">
                        @foreach ($customBoards as $board)
                            <article class="wm-moodboard-board" style="--board-accent: {{ $board['accent'] }}">
                                <div class="wm-moodboard-board-head">
                                    <div>
                                        <h3 class="wm-moodboard-board-title">{{ $board['title'] }}</h3>
                                        <p class="wm-moodboard-board-subtitle">{{ $board['subtitle'] }}</p>
                                        <div class="wm-moodboard-board-meta">
                                            <span class="wm-moodboard-tag">{{ $board['images']->count() }} images</span>
                                            <span class="wm-moodboard-tag">Custom board</span>
                                        </div>
                                    </div>

                                    <div class="wm-moodboard-board-actions">
                                        <button type="button" class="wm-moodboard-link" wire:click="openImageModal('custom', {{ $board['id'] }})">
                                            <x-heroicon-o-photo />
                                            <span>Add image</span>
                                        </button>
                                        <button type="button" class="wm-moodboard-icon-button is-danger" wire:click="promptDeleteBoard({{ $board['id'] }})">
                                            <x-heroicon-o-trash />
                                        </button>
                                    </div>
                                </div>

                                @if ($board['images']->isEmpty())
                                    <div class="wm-moodboard-empty">This board is ready. Add the first image to start shaping its direction.</div>
                                @else
                                    <div class="wm-moodboard-masonry">
                                        @foreach ($board['images'] as $image)
                                            <article class="wm-moodboard-card">
                                                <img class="wm-moodboard-card-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" alt="Moodboard image">
                                                <div class="wm-moodboard-card-body">
                                                    <div class="wm-moodboard-card-copy">
                                                        @if ($image->description)
                                                            <p class="wm-moodboard-card-title">{{ $image->description }}</p>
                                                        @endif
                                                        <p class="wm-moodboard-card-meta">{{ $image->image_category }}</p>
                                                    </div>
                                                    <button type="button" class="wm-moodboard-icon-button wm-moodboard-card-delete" wire:click="promptDeleteImage({{ $image->id }})">
                                                        <x-heroicon-o-trash />
                                                    </button>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        @if ($showBoardModal)
            <div class="wm-moodboard-modal-backdrop" wire:click="closeBoardModal"></div>
            <div class="wm-moodboard-modal" role="dialog" aria-modal="true">
                <div class="wm-moodboard-modal-head">
                    <div>
                        <h3 class="wm-moodboard-modal-title">Create Moodboard</h3>
                        <p class="wm-moodboard-modal-copy">Define a board title and an optional note so the visual direction is immediately clear.</p>
                    </div>

                    <button type="button" class="wm-moodboard-icon-button" wire:click="closeBoardModal">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>

                <div class="wm-moodboard-field">
                    <label for="moodboard-title">Board title</label>
                    <input id="moodboard-title" type="text" class="wm-moodboard-input" wire:model="boardForm.title">
                </div>

                <div class="wm-moodboard-field">
                    <label for="moodboard-notes">Notes</label>
                    <textarea id="moodboard-notes" class="wm-moodboard-textarea" wire:model="boardForm.notes"></textarea>
                </div>

                <div class="wm-moodboard-modal-actions">
                    <x-filament::button color="gray" wire:click="closeBoardModal">Cancel</x-filament::button>
                    <x-filament::button wire:click="saveBoard">Create board</x-filament::button>
                </div>
            </div>
        @endif

        @if ($showImageModal)
            <div class="wm-moodboard-modal-backdrop" wire:click="closeImageModal"></div>
            <div class="wm-moodboard-modal" role="dialog" aria-modal="true">
                <div class="wm-moodboard-modal-head">
                    <div>
                        <h3 class="wm-moodboard-modal-title">Add Image</h3>
                        <p class="wm-moodboard-modal-copy">Upload a new reference to enrich this moodboard.</p>
                    </div>

                    <button type="button" class="wm-moodboard-icon-button" wire:click="closeImageModal">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>

                <div class="wm-moodboard-field">
                    <label for="moodboard-image-upload">Image</label>
                    <input id="moodboard-image-upload" type="file" multiple wire:model="imageUploads">

                    @if (! empty($imageUploads))
                        <div class="wm-moodboard-preview">
                            @foreach ($imageUploads as $upload)
                                <div class="wm-moodboard-preview-item">
                                    <img src="{{ $upload->temporaryUrl() }}" alt="Preview">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="wm-moodboard-field">
                    <label for="moodboard-image-category">Category</label>
                    <select id="moodboard-image-category" class="wm-moodboard-select" wire:model="imageForm.image_category">
                        @foreach (\App\Models\ProjectImage::CATEGORY_OPTIONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="wm-moodboard-field">
                    <label for="moodboard-image-description">Description</label>
                    <textarea id="moodboard-image-description" class="wm-moodboard-textarea" wire:model="imageForm.description"></textarea>
                </div>

                <label class="wm-moodboard-toggle">
                    <input type="checkbox" wire:model="imageForm.is_client_visible">
                    <span>Visible to client</span>
                </label>

                <div class="wm-moodboard-modal-actions">
                    <x-filament::button color="gray" wire:click="closeImageModal">Cancel</x-filament::button>
                    <x-filament::button wire:click="saveImage">Save image</x-filament::button>
                </div>
            </div>
        @endif

        @if ($deleteImageId)
            <div class="wm-moodboard-modal-backdrop" wire:click="cancelDeleteImage"></div>
            <div class="wm-moodboard-modal" role="dialog" aria-modal="true">
                <div class="wm-moodboard-modal-head">
                    <div>
                        <h3 class="wm-moodboard-modal-title">Delete Image</h3>
                        <p class="wm-moodboard-modal-copy">This image will be permanently removed from the project moodboard.</p>
                    </div>
                </div>

                <div class="wm-moodboard-modal-actions">
                    <x-filament::button color="gray" wire:click="cancelDeleteImage">Cancel</x-filament::button>
                    <x-filament::button color="danger" wire:click="confirmDeleteImage">Delete image</x-filament::button>
                </div>
            </div>
        @endif

        @if ($deleteBoardId)
            <div class="wm-moodboard-modal-backdrop" wire:click="cancelDeleteBoard"></div>
            <div class="wm-moodboard-modal" role="dialog" aria-modal="true">
                <div class="wm-moodboard-modal-head">
                    <div>
                        <h3 class="wm-moodboard-modal-title">Delete Moodboard</h3>
                        <p class="wm-moodboard-modal-copy">Deleting this board will also remove all images currently attached to it.</p>
                    </div>
                </div>

                <div class="wm-moodboard-modal-actions">
                    <x-filament::button color="gray" wire:click="cancelDeleteBoard">Cancel</x-filament::button>
                    <x-filament::button color="danger" wire:click="confirmDeleteBoard">Delete board</x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
