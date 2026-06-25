<x-filament-panels::page>
    @php
        $record = $this->getRecord();
    @endphp

    <style>
        .wm-rsvp-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-rsvp-shell {
            width: min(1160px, calc(100% - 2rem));
            margin: 0 auto;
            display: grid;
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

        .wm-rsvp-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
        }

        .wm-rsvp-title {
            margin: 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: 1.35rem;
        }

        .wm-rsvp-copy {
            margin: 0.25rem 0 0;
            color: #746d66;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .wm-rsvp-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.65rem;
        }

        .wm-rsvp-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.65rem;
            padding: 0 1rem;
            border: 1px solid #b9975b;
            border-radius: 0.45rem;
            background: #b9975b;
            color: #fff;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-rsvp-button.is-secondary {
            background: #fffdfa;
            color: #7a5e28;
            border-color: #dfd0bf;
        }

        .wm-rsvp-button svg {
            width: 1rem;
            height: 1rem;
        }

        .wm-rsvp-list {
            display: grid;
            gap: 0.85rem;
            padding: 1rem;
        }

        .wm-rsvp-field {
            display: grid;
            grid-template-columns: 2.4rem minmax(0, 1fr);
            gap: 0.8rem;
            padding: 0.9rem;
            border: 1px solid #eadfce;
            border-radius: 0.9rem;
            background: #fffdf9;
        }

        .wm-rsvp-field.is-disabled {
            opacity: 0.66;
        }

        .wm-rsvp-drag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 0.6rem;
            background: #f4eee6;
            color: #7a5e28;
            cursor: grab;
        }

        .wm-rsvp-drag svg {
            width: 1rem;
            height: 1rem;
        }

        .wm-rsvp-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr) 9rem 13rem;
            gap: 0.75rem;
            align-items: end;
        }

        .wm-rsvp-field label,
        .wm-rsvp-toggle span {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-rsvp-input,
        .wm-rsvp-select,
        .wm-rsvp-textarea {
            width: 100%;
            min-height: 2.65rem;
            border: 1px solid #ddd2c5;
            border-radius: 0.45rem;
            background: #fff;
            padding: 0.65rem 0.78rem;
            color: #2d2a26;
        }

        .wm-rsvp-textarea {
            min-height: 4.8rem;
            resize: vertical;
        }

        .wm-rsvp-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            min-height: 2.65rem;
            border: 1px solid #ddd2c5;
            border-radius: 0.45rem;
            background: #fff;
            padding: 0 0.78rem;
            cursor: pointer;
        }

        .wm-rsvp-toggle span {
            margin-bottom: 0;
        }

        .wm-rsvp-toggle input {
            width: 1.05rem;
            height: 1.05rem;
            accent-color: #b9975b;
        }

        .wm-rsvp-options-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: start;
            margin-top: 0.75rem;
        }

        .wm-rsvp-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border: 0;
            border-radius: 999px;
            background: #f4eee6;
            color: #a96f66;
            cursor: pointer;
        }

        .wm-rsvp-cover {
            display: grid;
            grid-template-columns: minmax(14rem, 22rem) minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
            padding: 1rem;
        }

        .wm-rsvp-cover-preview {
            display: grid;
            place-items: center;
            min-height: 12rem;
            border: 1px solid #eadfce;
            border-radius: 0.9rem;
            background: #fbf7f0;
            color: #8b8178;
            overflow: hidden;
        }

        .wm-rsvp-cover-preview img {
            width: 100%;
            height: 100%;
            min-height: 12rem;
            object-fit: cover;
        }

        .wm-rsvp-cover-tools {
            display: grid;
            gap: 0.8rem;
        }

        .wm-rsvp-cover-title {
            margin: 0;
            color: #2d2a26;
            font-size: 0.9rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .wm-rsvp-cover-help {
            margin: 0;
            color: #746d66;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .wm-rsvp-file-input {
            display: block;
            width: 100%;
            border: 1px solid #ddd2c5;
            border-radius: 0.45rem;
            background: #fff;
            padding: 0.65rem 0.78rem;
            color: #2d2a26;
        }

        @media (max-width: 960px) {
            .wm-rsvp-shell {
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

            .wm-rsvp-head,
            .wm-rsvp-cover,
            .wm-rsvp-grid,
            .wm-rsvp-options-row {
                grid-template-columns: minmax(0, 1fr);
            }

            .wm-rsvp-head {
                align-items: stretch;
                flex-direction: column;
            }

            .wm-rsvp-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="wm-rsvp-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'guests',
        ])

        <div class="wm-rsvp-shell">
            <section class="wm-event-card wm-rsvp-head">
                <div>
                    <h3 class="wm-rsvp-title">RSVP Form Configuration</h3>
                    <p class="wm-rsvp-copy">Enable, label, type and reorder the fields guests will see when completing their RSVP.</p>
                </div>

                <div class="wm-rsvp-actions">
                    <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('guests', ['record' => $record]) }}" class="wm-rsvp-button is-secondary">
                        <x-heroicon-o-arrow-left />
                        <span>Back to guests</span>
                    </a>
                    <button type="button" class="wm-rsvp-button is-secondary" wire:click="addField">
                        <x-heroicon-o-plus />
                        <span>Add field</span>
                    </button>
                    <button type="button" class="wm-rsvp-button is-secondary" wire:click="resetDefaults">
                        <x-heroicon-o-arrow-path />
                        <span>Reset defaults</span>
                    </button>
                    <button type="button" class="wm-rsvp-button" wire:click="saveRsvpConfiguration">
                        <x-heroicon-o-check />
                        <span>Save</span>
                    </button>
                </div>
            </section>

            <section class="wm-event-card wm-rsvp-cover">
                <div class="wm-rsvp-cover-preview">
                    @if ($rsvpCoverImage)
                        <img src="{{ $rsvpCoverImage->temporaryUrl() }}" alt="">
                    @elseif ($record->cover_image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->cover_image_path))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($record->cover_image_path) }}" alt="">
                    @else
                        <span>No RSVP cover image</span>
                    @endif
                </div>

                <div class="wm-rsvp-cover-tools">
                    <div>
                        <h3 class="wm-rsvp-cover-title">RSVP cover image</h3>
                        <p class="wm-rsvp-cover-help">Used as the hero image on the RSVP page. Recommended ratio: 16:9 or 4:3, at least 1600px wide.</p>
                    </div>

                    <input type="file" class="wm-rsvp-file-input" wire:model="rsvpCoverImage" accept="image/*">

                    @error('rsvpCoverImage')
                        <p class="wm-rsvp-cover-help">{{ $message }}</p>
                    @enderror

                    <div class="wm-rsvp-actions" wire:loading.remove wire:target="rsvpCoverImage,saveRsvpCoverImage,removeRsvpCoverImage">
                        <button type="button" class="wm-rsvp-button" wire:click="saveRsvpCoverImage" @disabled(! $rsvpCoverImage)>
                            <x-heroicon-o-check />
                            <span>Save image</span>
                        </button>
                        @if ($record->cover_image_path)
                            <button type="button" class="wm-rsvp-button is-secondary" wire:click="removeRsvpCoverImage">
                                <x-heroicon-o-trash />
                                <span>Remove image</span>
                            </button>
                        @endif
                    </div>

                    <p class="wm-rsvp-cover-help" wire:loading wire:target="rsvpCoverImage,saveRsvpCoverImage,removeRsvpCoverImage">Uploading image...</p>
                </div>
            </section>

            <section class="wm-event-card wm-rsvp-list" x-data="{ dragged: null }">
                @foreach ($fields as $index => $field)
                    <article
                        class="wm-rsvp-field {{ ($field['enabled'] ?? false) ? '' : 'is-disabled' }}"
                        draggable="true"
                        x-on:dragstart="dragged = {{ $index }}"
                        x-on:dragover.prevent
                        x-on:drop.prevent="$wire.moveRsvpField(dragged, {{ $index }}); dragged = null"
                        wire:key="rsvp-field-{{ $field['key'] }}"
                    >
                        <div class="wm-rsvp-drag" title="Drag to reorder">
                            <x-heroicon-o-bars-3 />
                        </div>

                        <div>
                            <div class="wm-rsvp-grid">
                                <label>
                                    Label
                                    <input type="text" class="wm-rsvp-input" wire:model="fields.{{ $index }}.label">
                                </label>

                                <label>
                                    Help text
                                    <input type="text" class="wm-rsvp-input" wire:model="fields.{{ $index }}.help_text">
                                </label>

                                <label>
                                    Type
                                    <select class="wm-rsvp-select" wire:model.live="fields.{{ $index }}.type">
                                        <option value="text">Text</option>
                                        <option value="select">Select</option>
                                        <option value="checkbox">Checkbox</option>
                                    </select>
                                </label>

                                <label>
                                    Response
                                    <select class="wm-rsvp-select" wire:model="fields.{{ $index }}.response_scope">
                                        <option value="aggregate">Aggregated</option>
                                        <option value="per_guest">For each guest</option>
                                    </select>
                                </label>
                            </div>

                            <div class="wm-rsvp-options-row">
                                @if (($fields[$index]['type'] ?? 'text') === 'select')
                                    <label>
                                        Select values
                                        <textarea class="wm-rsvp-textarea" wire:model="fields.{{ $index }}.options_text" placeholder="One option per line"></textarea>
                                    </label>
                                @else
                                    <div></div>
                                @endif

                                <div class="wm-rsvp-actions">
                                    <label class="wm-rsvp-toggle">
                                        <span>Enabled</span>
                                        <input type="checkbox" wire:model="fields.{{ $index }}.enabled">
                                    </label>
                                    <button type="button" class="wm-rsvp-remove" wire:click="removeField({{ $index }})" title="Remove field">
                                        <x-heroicon-o-trash />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-filament-panels::page>
