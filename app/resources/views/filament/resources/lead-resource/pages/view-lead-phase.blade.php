<x-filament-panels::page>
    <style>
        .lead-phase-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .lead-phase-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(18rem, 0.8fr);
            gap: 1rem;
            align-items: start;
        }

        .lead-phase-panel {
            border: 1px solid #e8e3dc;
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 16px 36px rgba(45, 42, 38, 0.05);
            padding: 1.15rem 1.2rem;
        }

        .lead-phase-toolbar {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .lead-phase-inline-actions {
            display: flex;
            gap: 0.65rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .lead-phase-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.65rem;
            padding: 0 1rem;
            border: 1px solid #ded4c8;
            border-radius: 0.9rem;
            background: #fbf8f4;
            color: #5f5953;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
        }

        .lead-phase-button.is-primary {
            border-color: rgba(46, 74, 98, 0.18);
            background: rgba(46, 74, 98, 0.08);
            color: #2e4a62;
        }

        .lead-phase-button.is-disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .lead-phase-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            color: #2d2a26;
        }

        .lead-phase-copy {
            margin: 0.35rem 0 0;
            color: #8b847d;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .lead-phase-html {
            min-height: 14rem;
            border: 1px solid #eee7e0;
            border-radius: 1rem;
            background: #fffdfa;
            padding: 1rem 1.05rem;
            color: #3d3833;
            line-height: 1.75;
        }

        .lead-phase-html h1,
        .lead-phase-html h2,
        .lead-phase-html h3 {
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            margin-top: 0;
        }

        .lead-phase-empty {
            min-height: 14rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            border: 1px dashed #dfd7ce;
            border-radius: 1rem;
            background: #fffdfa;
            padding: 1.2rem;
            color: #958d85;
            line-height: 1.7;
        }

        .lead-phase-side {
            display: grid;
            gap: 1rem;
        }

        .lead-phase-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .lead-phase-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.4rem 0.7rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .lead-phase-badge.blue { background: rgba(46, 74, 98, 0.12); color: #2e4a62; }
        .lead-phase-badge.olive { background: rgba(122, 143, 123, 0.16); color: #617563; }
        .lead-phase-badge.gold { background: rgba(201, 169, 106, 0.16); color: #9a7a39; }
        .lead-phase-badge.rose { background: rgba(227, 183, 178, 0.24); color: #9b6d69; }

        .lead-phase-meta {
            display: grid;
            gap: 0.75rem;
        }

        .lead-phase-meta-card,
        .lead-phase-note {
            border: 1px solid #eee7e0;
            border-radius: 1rem;
            background: #fffdfa;
            padding: 0.9rem 1rem;
        }

        .lead-phase-meta-label {
            margin: 0;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #a09790;
        }

        .lead-phase-meta-value {
            margin: 0.38rem 0 0;
            font-size: 0.92rem;
            line-height: 1.5;
            color: #2d2a26;
        }

        .lead-phase-note-time {
            margin: 0 0 0.35rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: #8b847d;
        }

        .lead-phase-note-copy {
            margin: 0;
            font-size: 0.88rem;
            line-height: 1.6;
            color: #4e4a46;
            white-space: pre-wrap;
        }

        .lead-phase-link {
            color: #2e4a62;
            font-weight: 700;
            text-decoration: none;
        }

        @media (max-width: 1100px) {
            .lead-phase-grid {
                grid-template-columns: 1fr;
            }

            .lead-phase-toolbar {
                flex-direction: column;
                align-items: start;
            }

            .lead-phase-inline-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="lead-phase-layout">
        <div class="lead-phase-grid">
            <div class="lead-phase-panel">
                <div class="lead-phase-toolbar">
                    <div>
                        <h2 class="lead-phase-title">{{ $phaseTitle }}</h2>
                        <p class="lead-phase-copy">{{ $phaseDescription }}</p>
                    </div>

                    <div class="lead-phase-inline-actions">
                        <button type="button" class="lead-phase-button is-primary" wire:click="mountAction('generatePhase')">
                            {{ str_contains(strtolower($phaseTitle), 'contract') ? 'Generate contract' : 'Generate proposal' }}
                        </button>
                        <button type="button" class="lead-phase-button" wire:click="mountAction('editContent')">
                            Edit content
                        </button>
                        <button type="button" class="lead-phase-button is-disabled" disabled>
                            Export PDF
                        </button>
                    </div>
                </div>

                @if (filled($phaseContentHtml))
                    <div class="lead-phase-html">{!! $phaseContentHtml !!}</div>
                @else
                    <div class="lead-phase-empty">{{ $phaseEmptyCopy }}</div>
                @endif
            </div>

            <aside class="lead-phase-side">
                @if (! empty($asideData['status_badges']))
                    <div class="lead-phase-panel">
                        <h3 class="lead-phase-title">Phase status</h3>
                        <div class="lead-phase-badges" style="margin-top: 1rem;">
                            @foreach ($asideData['status_badges'] as $badge)
                                <span class="lead-phase-badge {{ $badge['tone'] }}">{{ $badge['label'] }}: {{ $badge['value'] }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($asideData['meta']))
                    <div class="lead-phase-panel">
                        <h3 class="lead-phase-title">Key dates</h3>
                        <div class="lead-phase-meta" style="margin-top: 1rem;">
                            @foreach ($asideData['meta'] as $entry)
                                <div class="lead-phase-meta-card">
                                    <p class="lead-phase-meta-label">{{ $entry['label'] }}</p>
                                    <p class="lead-phase-meta-value">{{ $entry['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($asideData['notes_log']))
                    <div class="lead-phase-panel">
                        <h3 class="lead-phase-title">Notes and variations</h3>
                        <div class="lead-phase-meta" style="margin-top: 1rem;">
                            @foreach ($asideData['notes_log'] as $entry)
                                <div class="lead-phase-note">
                                    <p class="lead-phase-note-time">{{ \Illuminate\Support\Carbon::parse($entry['created_at'])->format('d/m/Y H:i') }}</p>
                                    <p class="lead-phase-note-copy">{{ $entry['note'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($asideData['signed_contract_url']) || ! empty($asideData['project']))
                    <div class="lead-phase-panel">
                        <h3 class="lead-phase-title">Execution</h3>
                        <div class="lead-phase-meta" style="margin-top: 1rem;">
                            @if (! empty($asideData['signed_contract_url']))
                                <div class="lead-phase-meta-card">
                                    <p class="lead-phase-meta-label">Signed contract</p>
                                    <p class="lead-phase-meta-value">
                                        <a href="{{ $asideData['signed_contract_url'] }}" target="_blank" class="lead-phase-link">Open signed contract</a>
                                    </p>
                                </div>
                            @endif

                            @if (! empty($asideData['project']))
                                <div class="lead-phase-meta-card">
                                    <p class="lead-phase-meta-label">Event status</p>
                                    <p class="lead-phase-meta-value">
                                        Project already created:
                                        <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $asideData['project']]) }}" class="lead-phase-link">
                                            {{ $asideData['project']->name }}
                                        </a>
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
