<div>
@php
    $isVisual = $this->usesVisualProposalFlow();
    $hasVisualProposal = (bool) $this->getRecord()->proposal_layout;
    $isNewProposal = $isVisual && ! $hasVisualProposal;
@endphp
    <x-filament-panels::page>
        <style>
            .visual-proposal-layout { display:grid; grid-template-columns:minmax(200px,1fr) minmax(0,5fr); gap:16px; align-items:start; }
            .visual-proposal-main { min-width:0; }
            .legacy-proposal-toolbar { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; margin-bottom:16px; }
            .visual-proposal-actions { display:grid; gap:8px; margin-top:12px; }
            .visual-proposal-actions .fi-btn { width:100%; white-space:normal; text-align:center; font-size:.75rem; }
            .visual-proposal-aside { display:grid; gap:14px; min-width:0; }
            .visual-proposal-card { padding:14px 12px; border:1px solid #e8e3dc; border-radius:18px; background:rgba(255,255,255,.92); box-shadow:0 12px 28px rgba(45,42,38,.04); }
            .visual-proposal-card h3 { margin:0 0 14px; font-family:'Cinzel',serif; font-size:1rem; color:#2d2a26; }
            .visual-proposal-badges { display:grid; gap:8px; }
            .visual-proposal-badge { display:block; padding:8px 10px; border-radius:14px; font-size:.67rem; font-weight:700; line-height:1.5; text-transform:uppercase; overflow-wrap:anywhere; }
            .visual-proposal-badge.olive { background:rgba(122,143,123,.16); color:#617563; }
            .visual-proposal-badge.gold { background:rgba(201,169,106,.16); color:#9a7a39; }
            .visual-proposal-badge.blue { background:rgba(46,74,98,.12); color:#2e4a62; }
            .visual-proposal-badge.rose { background:rgba(227,183,178,.24); color:#9b6d69; }
            .visual-proposal-meta { display:grid; gap:10px; }
            .visual-proposal-meta-item { padding:10px; border:1px solid #eee7e0; border-radius:12px; background:#fffdfa; }
            .visual-proposal-meta-label { margin:0; font-size:.62rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#a09790; }
            .visual-proposal-meta-value { margin:6px 0 0; font-size:.8rem; line-height:1.5; color:#2d2a26; overflow-wrap:anywhere; }
            .visual-proposal-note { margin-top:10px; font-size:.8rem; line-height:1.5; white-space:pre-wrap; overflow-wrap:anywhere; }
            .legacy-proposal-content { min-height:14rem; border:1px solid #eee7e0; border-radius:1rem; background:#fffdfa; padding:1rem 1.05rem; color:#3d3833; line-height:1.75; }
            .legacy-proposal-content h1, .legacy-proposal-content h2, .legacy-proposal-content h3 { color:#2d2a26; font-family:'Cinzel',serif; margin-top:0; }
            .legacy-proposal-empty { min-height:14rem; display:flex; align-items:center; justify-content:center; text-align:center; border:1px dashed #dfd7ce; border-radius:1rem; background:#fffdfa; padding:1.2rem; color:#958d85; line-height:1.7; }
            @media(max-width:760px) { .visual-proposal-layout { grid-template-columns:minmax(0,1fr); } }
        </style>
        <div class="visual-proposal-layout">
            <aside class="visual-proposal-aside">
                <section class="visual-proposal-card">
                    <h3>Proposal</h3>
                    @if ($isVisual)
                        <p class="visual-proposal-meta-label">Template</p>
                        <p class="visual-proposal-meta-value">{{ $hasVisualProposal ? $asideData['template_name'] : 'Not selected' }}</p>
                    @endif
                    <div class="visual-proposal-actions">
                        @if ($isVisual)
                            <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="mountAction('generatePhase')">{{ $hasVisualProposal ? 'Regenerate / change template' : 'Choose template' }}</x-filament::button>
                        @endif
                        @if (! $isNewProposal)
                            <x-filament::button icon="heroicon-o-arrow-down-tray" tag="a" href="{{ $exportPdfUrl }}" target="_blank">{{ $isVisual ? 'Download PDF' : 'Export PDF' }}</x-filament::button>
                        @endif
                    </div>
                </section>
                <section class="visual-proposal-card">
                    <h3>Sending status</h3>
                    <span class="visual-proposal-badge olive">{{ $asideData['status_badges'][0]['value'] }}</span>
                    <p class="visual-proposal-meta-value">{{ $asideData['meta'][0]['value'] }}</p>
                    <div class="visual-proposal-actions">
                        <x-filament::button :disabled="$isNewProposal" icon="heroicon-o-paper-airplane" wire:click="mountAction('markProposalSent')">Mark proposal as sent</x-filament::button>
                    </div>
                </section>
                <section class="visual-proposal-card">
                    <h3>Client response</h3>
                    <span class="visual-proposal-badge gold">{{ $asideData['status_badges'][1]['value'] }}</span>
                    <p class="visual-proposal-meta-value">{{ $asideData['meta'][1]['value'] }}</p>
                    <div class="visual-proposal-actions">
                        <x-filament::button :disabled="$isNewProposal" color="gray" icon="heroicon-o-chat-bubble-bottom-center-text" wire:click="mountAction('saveClientResponse')">Save client response</x-filament::button>
                        <x-filament::button :disabled="$isNewProposal" color="gray" icon="heroicon-o-pencil-square" wire:click="mountAction('addProposalNote')">Add note / variation</x-filament::button>
                    </div>
                </section>
                @if (! empty($asideData['notes_log']))
                    <section class="visual-proposal-card">
                        <h3>Notes and variations</h3>
                        @foreach ($asideData['notes_log'] as $entry)
                            <p class="visual-proposal-note"><strong>{{ \Illuminate\Support\Carbon::parse($entry['created_at'])->format('d/m/Y H:i') }}</strong><br>{{ $entry['note'] }}</p>
                        @endforeach
                    </section>
                @endif
            </aside>
            <div class="visual-proposal-main">
                @if (! $isVisual)
                    <div class="legacy-proposal-toolbar">
                        <x-filament::button color="gray" icon="heroicon-o-sparkles" wire:click="mountAction('generatePhase')">Generate</x-filament::button>
                        <x-filament::button color="gray" icon="heroicon-o-adjustments-horizontal" wire:click="mountAction('configureProposal')">Configure proposal</x-filament::button>
                        <x-filament::button color="gray" icon="heroicon-o-photo" wire:click="mountAction('configureProposalImages')">Configure images</x-filament::button>
                        @if (! $this->getRecord()->proposal_sent_at)
                            <x-filament::button color="gray" icon="heroicon-o-document-duplicate" wire:click="mountAction('createVisualProposal')">Switch to visual proposal editor</x-filament::button>
                        @endif
                    </div>
                @endif
                @if ($isNewProposal)
                    <section class="visual-proposal-card" style="padding:32px;">
                        <h3>Create your proposal</h3>
                        <p class="visual-proposal-meta-value">Choose a template to create your visual proposal, then edit its texts and images directly on the pages.</p>
                        <x-filament::button style="margin-top:20px;" icon="heroicon-o-document-plus" wire:click="mountAction('generatePhase')">Create visual proposal</x-filament::button>
                    </section>
                @elseif ($isVisual)
                    @include('filament.proposals.preview', ['previewUrl' => route('admin.leads.proposal.preview', $this->getRecord())])
                @elseif (filled($phaseContentHtml))
                    <div class="legacy-proposal-content">{!! $phaseContentHtml !!}</div>
                @else
                    <div class="legacy-proposal-empty">{{ $phaseEmptyCopy }}</div>
                @endif
            </div>
        </div>
        <x-filament-actions::modals />
    </x-filament-panels::page>
</div>
