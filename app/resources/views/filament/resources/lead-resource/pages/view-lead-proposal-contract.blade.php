<x-filament-panels::page>
    <style>
        .lead-proposal-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .lead-proposal-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
            gap: 1rem;
        }

        .lead-proposal-panel {
            border: 1px solid #e8e3dc;
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 16px 36px rgba(45, 42, 38, 0.05);
            padding: 1.15rem 1.2rem;
        }

        .lead-proposal-panel-title {
            margin: 0 0 0.35rem;
            font-family: 'Cinzel', serif;
            font-size: 1rem;
            color: #2d2a26;
        }

        .lead-proposal-panel-copy {
            margin: 0 0 1rem;
            font-size: 0.85rem;
            line-height: 1.55;
            color: #8b847d;
        }

        .lead-proposal-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .lead-proposal-kpi {
            border: 1px solid #eee7e0;
            border-radius: 1rem;
            background: #fffdfa;
            padding: 0.9rem 1rem;
        }

        .lead-proposal-kpi-label {
            margin: 0;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #8b847d;
        }

        .lead-proposal-kpi-value {
            margin: 0.45rem 0 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d2a26;
        }

        .lead-proposal-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .lead-proposal-meta-card,
        .lead-proposal-note {
            border: 1px solid #eee7e0;
            border-radius: 1rem;
            background: #fffdfa;
            padding: 0.9rem 1rem;
        }

        .lead-proposal-meta-label {
            margin: 0;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #a09790;
        }

        .lead-proposal-meta-value {
            margin: 0.38rem 0 0;
            font-size: 0.92rem;
            line-height: 1.5;
            color: #2d2a26;
        }

        .lead-proposal-statuses {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .lead-proposal-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.4rem 0.7rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .lead-proposal-badge.blue {
            background: rgba(46, 74, 98, 0.12);
            color: #2e4a62;
        }

        .lead-proposal-badge.olive {
            background: rgba(122, 143, 123, 0.16);
            color: #617563;
        }

        .lead-proposal-badge.gold {
            background: rgba(201, 169, 106, 0.16);
            color: #9a7a39;
        }

        .lead-proposal-badge.rose {
            background: rgba(227, 183, 178, 0.24);
            color: #9b6d69;
        }

        .lead-proposal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 1rem;
        }

        .lead-proposal-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.85rem;
            padding: 0.72rem 1rem;
            background: #2e4a62;
            color: #fff;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .lead-proposal-link.secondary {
            background: #f0ebe5;
            color: #2d2a26;
        }

        .lead-proposal-log {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .lead-proposal-note-time {
            margin: 0 0 0.35rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: #8b847d;
        }

        .lead-proposal-note-copy {
            margin: 0;
            font-size: 0.88rem;
            line-height: 1.6;
            color: #4e4a46;
            white-space: pre-wrap;
        }

        @media (max-width: 1100px) {
            .lead-proposal-grid,
            .lead-proposal-kpis,
            .lead-proposal-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="lead-proposal-layout">
        <div class="lead-proposal-panel">
            <h2 class="lead-proposal-panel-title">Proposal phase summary</h2>
            <p class="lead-proposal-panel-copy">
                This page combines the essential lead profile with the current budget composition and proposal / contract phase controls.
            </p>

            <div class="lead-proposal-statuses">
                <span class="lead-proposal-badge blue">
                    Lead status: {{ \App\Models\Lead::STATUS_OPTIONS[$lead->status] ?? $lead->status }}
                </span>
                <span class="lead-proposal-badge olive">
                    Proposal: {{ $lead->proposal_sent_at ? 'Sent' : 'Draft' }}
                </span>
                <span class="lead-proposal-badge gold">
                    Client response: {{ \App\Models\Lead::PROPOSAL_RESPONSE_OPTIONS[$lead->proposal_response_status] ?? 'Not set' }}
                </span>
                <span class="lead-proposal-badge rose">
                    Contract: {{ $lead->contract_received_at ? 'Received' : ($lead->contract_sent_at ? 'Sent' : 'Pending') }}
                </span>
            </div>

            <div class="lead-proposal-kpis">
                <div class="lead-proposal-kpi">
                    <p class="lead-proposal-kpi-label">Vendors budget</p>
                    <p class="lead-proposal-kpi-value">EUR {{ number_format($budget['vendors'], 0, ',', '.') }}</p>
                </div>
                <div class="lead-proposal-kpi">
                    <p class="lead-proposal-kpi-label">Planner fee</p>
                    <p class="lead-proposal-kpi-value">EUR {{ number_format($budget['planner'], 0, ',', '.') }}</p>
                </div>
                <div class="lead-proposal-kpi">
                    <p class="lead-proposal-kpi-label">Extra services</p>
                    <p class="lead-proposal-kpi-value">EUR {{ number_format($budget['extras'], 0, ',', '.') }}</p>
                </div>
                <div class="lead-proposal-kpi">
                    <p class="lead-proposal-kpi-label">Estimated total</p>
                    <p class="lead-proposal-kpi-value">EUR {{ number_format($budget['grand_total'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="lead-proposal-grid">
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <div class="lead-proposal-panel">
                    <h3 class="lead-proposal-panel-title">Lead essentials</h3>
                    <div class="lead-proposal-meta">
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Couple</p>
                            <p class="lead-proposal-meta-value">{{ $lead->couple_name }}</p>
                        </div>
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Email</p>
                            <p class="lead-proposal-meta-value">{{ $lead->email ?: 'Not available' }}</p>
                        </div>
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Region</p>
                            <p class="lead-proposal-meta-value">{{ $lead->desired_region ?: 'To define' }}</p>
                        </div>
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Wedding period</p>
                            <p class="lead-proposal-meta-value">{{ $lead->wedding_period ?: 'To define' }}</p>
                        </div>
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Guests</p>
                            <p class="lead-proposal-meta-value">{{ $lead->estimated_guest_count ?: 'To define' }}</p>
                        </div>
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Declared budget</p>
                            <p class="lead-proposal-meta-value">
                                {{ $lead->budget_amount ? 'EUR ' . number_format((float) $lead->budget_amount, 0, ',', '.') : 'Not specified' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="lead-proposal-panel">
                    <h3 class="lead-proposal-panel-title">Proposal documents</h3>
                    <p class="lead-proposal-panel-copy">
                        Proposal sent: {{ $lead->proposal_sent_at?->format('d/m/Y H:i') ?: 'Not yet sent' }}<br>
                        Response saved: {{ $lead->proposal_response_at?->format('d/m/Y H:i') ?: 'No response yet' }}
                    </p>

                    <div class="lead-proposal-actions">
                        <a href="{{ $proposalPdfUrl }}" class="lead-proposal-link">Download proposal PDF</a>
                    </div>
                </div>

                <div class="lead-proposal-panel">
                    <h3 class="lead-proposal-panel-title">Notes and variations</h3>
                    <div class="lead-proposal-log">
                        @forelse($proposalNotesLog as $entry)
                            <div class="lead-proposal-note">
                                <p class="lead-proposal-note-time">
                                    {{ \Illuminate\Support\Carbon::parse($entry['created_at'])->format('d/m/Y H:i') }}
                                </p>
                                <p class="lead-proposal-note-copy">{{ $entry['note'] }}</p>
                            </div>
                        @empty
                            <div class="lead-proposal-note">
                                <p class="lead-proposal-note-copy">No notes or variations recorded yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:1rem;">
                <div class="lead-proposal-panel">
                    <h3 class="lead-proposal-panel-title">Budget snapshot</h3>
                    <div class="lead-proposal-meta">
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Vendor categories</p>
                            <p class="lead-proposal-meta-value">{{ count($lead->budget_vendors ?? []) }}</p>
                        </div>
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Planner extra lines</p>
                            <p class="lead-proposal-meta-value">{{ count($lead->budget_wedding_planner_extra_services ?? []) }}</p>
                        </div>
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Special packages</p>
                            <p class="lead-proposal-meta-value">{{ count($lead->budget_wedding_planner_special_packages ?? []) }}</p>
                        </div>
                        <div class="lead-proposal-meta-card">
                            <p class="lead-proposal-meta-label">Budget page</p>
                            <p class="lead-proposal-meta-value">
                                <a href="{{ \App\Filament\Resources\LeadResource::getUrl('budget-composition', ['record' => $lead]) }}">Open budget composition</a>
                            </p>
                        </div>
                    </div>
                </div>

                @if($isProposalApproved)
                    <div class="lead-proposal-panel">
                        <h3 class="lead-proposal-panel-title">Contract</h3>
                        <p class="lead-proposal-panel-copy">
                            Contract sent: {{ $lead->contract_sent_at?->format('d/m/Y H:i') ?: 'Not yet sent' }}<br>
                            Signed contract received: {{ $lead->contract_received_at?->format('d/m/Y H:i') ?: 'Not yet received' }}
                        </p>

                        <div class="lead-proposal-actions">
                            <a href="{{ $contractPdfUrl }}" class="lead-proposal-link">Download contract PDF</a>
                            @if($signedContractUrl)
                                <a href="{{ $signedContractUrl }}" target="_blank" class="lead-proposal-link secondary">Open signed contract</a>
                            @endif
                        </div>

                        @if($lead->project)
                            <div class="lead-proposal-note" style="margin-top:1rem;">
                                <p class="lead-proposal-note-time">Event status</p>
                                <p class="lead-proposal-note-copy">
                                    Project already created: <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $lead->project]) }}">{{ $lead->project->name }}</a>
                                </p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="lead-proposal-panel">
                        <h3 class="lead-proposal-panel-title">Contract</h3>
                        <p class="lead-proposal-panel-copy">
                            The contract panel becomes active when the proposal response is marked as <strong>Accepted</strong> or <strong>Approved</strong>.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
