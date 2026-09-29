<x-filament-panels::page>
    @php
        $stats = $this->getAnalytics();
        $years = $this->getAvailableYears();
        $monthlyMax = max(1, collect($stats['monthly'])->max('leads'));
        $trendPoints = collect($stats['monthly'])->map(function ($item, $index) use ($monthlyMax) {
            $x = 38 + ($index * (642 / 11));
            $y = 196 - (($item['leads'] / $monthlyMax) * 150);
            return round($x, 1).','.round($y, 1);
        })->implode(' ');
        $conversionPoints = collect($stats['monthly'])->map(function ($item, $index) use ($monthlyMax) {
            $x = 38 + ($index * (642 / 11));
            $y = 196 - (($item['converted'] / $monthlyMax) * 150);
            return round($x, 1).','.round($y, 1);
        })->implode(' ');
        $statusColors = ['#2E4A62', '#7A8F7B', '#C9A96A', '#D99B86', '#8F7B8D', '#A9B6A4', '#C6B8A5', '#766B63'];
        $statusTotal = max(1, collect($stats['statuses'])->sum('value'));
        $cursor = 0;
        $donutStops = collect($stats['statuses'])->map(function ($item, $index) use (&$cursor, $statusTotal, $statusColors) {
            $start = $cursor;
            $cursor += ($item['value'] / $statusTotal) * 100;
            $color = $statusColors[$index % count($statusColors)];
            return $color.' '.round($start, 2).'% '.round($cursor, 2).'%';
        })->implode(', ');
        $donutStops = $donutStops ?: '#e8e3dc 0 100%';
    @endphp

    <style>
        .lead-stats { display:flex; flex-direction:column; gap:1.25rem; color:#2d2a26; }
        .ls-hero, .ls-panel, .ls-kpi { border:1px solid #e7e0d7; background:rgba(255,255,255,.94); box-shadow:0 16px 42px rgba(58,48,40,.055); }
        .ls-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:1.5rem; padding:1.55rem 1.7rem; border-radius:1.5rem; background:radial-gradient(circle at 14% 0,rgba(201,169,106,.22),transparent 30%),radial-gradient(circle at 88% 100%,rgba(122,143,123,.18),transparent 35%),linear-gradient(135deg,#fff,#f8f4ee); }
        .ls-eyebrow { margin:0 0 .45rem; color:#9a7739; font-size:.7rem; font-weight:800; letter-spacing:.2em; text-transform:uppercase; }
        .ls-title { margin:0; font-family:'Cinzel',serif; font-size:clamp(1.6rem,3vw,2.45rem); line-height:1.08; }
        .ls-intro { max-width:48rem; margin:.7rem 0 0; color:#766f68; font-size:.92rem; line-height:1.65; }
        .ls-filter { display:flex; align-items:flex-end; gap:.65rem; flex:0 0 auto; }
        .ls-field label { display:block; margin-bottom:.35rem; color:#766f68; font-size:.67rem; font-weight:800; letter-spacing:.13em; text-transform:uppercase; }
        .ls-select, .ls-all { height:2.65rem; border:1px solid #d9cdbf; border-radius:.75rem; background:#fff; color:#3f3934; font-size:.86rem; font-weight:700; }
        .ls-select { min-width:9rem; padding:0 2.2rem 0 .85rem; }
        .ls-all { padding:0 1rem; cursor:pointer; transition:.15s ease; }
        .ls-all:hover, .ls-all.is-active { border-color:#7A8F7B; background:#7A8F7B; color:white; }
        .ls-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:.85rem; }
        .ls-kpi { min-height:8.6rem; padding:1.05rem 1.1rem; border-radius:1.15rem; position:relative; overflow:hidden; }
        .ls-kpi:after { content:''; position:absolute; right:-1.8rem; bottom:-2.4rem; width:6rem; height:6rem; border-radius:50%; background:var(--accent-soft); }
        .ls-kpi-label { margin:0; color:#827a73; font-size:.66rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
        .ls-kpi-value { margin:.65rem 0 .2rem; font-family:'Cinzel',serif; font-size:1.65rem; line-height:1; color:var(--accent); }
        .ls-kpi-meta { margin:0; color:#8d857e; font-size:.74rem; line-height:1.4; }
        .ls-grid-2 { display:grid; grid-template-columns:minmax(0,1.55fr) minmax(20rem,.85fr); gap:1rem; }
        .ls-grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
        .ls-panel { padding:1.25rem 1.3rem; border-radius:1.3rem; min-width:0; }
        .ls-panel-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1.05rem; }
        .ls-panel-title { margin:0; font-family:'Cinzel',serif; font-size:1rem; color:#37322e; }
        .ls-panel-copy { margin:.3rem 0 0; color:#8c847d; font-size:.75rem; line-height:1.45; }
        .ls-pill { flex:0 0 auto; padding:.35rem .6rem; border-radius:999px; background:#f0ece6; color:#786f67; font-size:.67rem; font-weight:800; }
        .ls-trend { width:100%; min-height:15.6rem; display:block; overflow:visible; }
        .ls-chart-label { fill:#918981; font-size:10px; font-weight:600; }
        .ls-legend { display:flex; gap:1rem; margin-top:.3rem; color:#776f68; font-size:.72rem; }
        .ls-dot { display:inline-block; width:.55rem; height:.55rem; margin-right:.35rem; border-radius:50%; }
        .ls-funnel { display:flex; flex-direction:column; gap:.9rem; }
        .ls-funnel-row { display:grid; grid-template-columns:6.6rem 1fr 3rem; gap:.65rem; align-items:center; }
        .ls-funnel-label { font-size:.76rem; font-weight:700; color:#5d5650; }
        .ls-funnel-track, .ls-bar-track { height:.68rem; overflow:hidden; border-radius:999px; background:#eee9e3; }
        .ls-funnel-fill, .ls-bar-fill { height:100%; min-width:2px; border-radius:999px; background:linear-gradient(90deg,#2E4A62,#7A8F7B); }
        .ls-funnel-value { text-align:right; font-family:'Cinzel',serif; font-size:.83rem; }
        .ls-summary-strip { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.6rem; margin-top:1.2rem; padding-top:1rem; border-top:1px solid #ece6df; }
        .ls-summary-item strong { display:block; color:#2E4A62; font-family:'Cinzel',serif; font-size:1rem; }
        .ls-summary-item span { color:#938b84; font-size:.66rem; }
        .ls-source-table { width:100%; border-collapse:collapse; }
        .ls-source-table th { padding:.55rem .65rem; border-bottom:1px solid #e7e0d8; color:#928981; font-size:.65rem; letter-spacing:.1em; text-align:left; text-transform:uppercase; }
        .ls-source-table td { padding:.72rem .65rem; border-bottom:1px solid #f0ebe5; color:#5e5751; font-size:.79rem; }
        .ls-source-table tr:last-child td { border-bottom:0; }
        .ls-source-name { font-weight:750; color:#3c3733; }
        .ls-source-meter { display:flex; align-items:center; gap:.6rem; min-width:10rem; }
        .ls-source-meter .ls-bar-track { width:7rem; }
        .ls-rate { display:inline-flex; min-width:3.4rem; justify-content:center; padding:.28rem .5rem; border-radius:999px; background:rgba(122,143,123,.14); color:#5b745e; font-weight:800; font-size:.69rem; }
        .ls-donut-wrap { display:grid; grid-template-columns:8.2rem 1fr; align-items:center; gap:1.1rem; }
        .ls-donut { width:8.2rem; aspect-ratio:1; border-radius:50%; display:grid; place-items:center; }
        .ls-donut:after { content:''; width:4.9rem; aspect-ratio:1; border-radius:50%; background:#fff; box-shadow:0 0 0 1px rgba(231,224,215,.65); }
        .ls-legend-list { display:flex; flex-direction:column; gap:.43rem; }
        .ls-legend-row { display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:.45rem; align-items:center; color:#655e58; font-size:.72rem; }
        .ls-bars { display:flex; flex-direction:column; gap:.72rem; }
        .ls-bar-row { display:grid; grid-template-columns:minmax(6rem,1fr) minmax(5rem,1.15fr) 2.4rem; gap:.65rem; align-items:center; }
        .ls-bar-label { overflow:hidden; color:#5f5852; font-size:.73rem; font-weight:650; text-overflow:ellipsis; white-space:nowrap; }
        .ls-bar-value { text-align:right; color:#4b4540; font-size:.72rem; font-weight:800; }
        .ls-empty { padding:2.4rem 1rem; border:1px dashed #ded5cb; border-radius:1rem; color:#9a928a; font-size:.78rem; text-align:center; }
        .ls-insight-note { margin-top:1rem; padding:.75rem .85rem; border-radius:.8rem; background:#f7f3ed; color:#817970; font-size:.7rem; line-height:1.5; }
        .dark .lead-stats { color:#eee9e3; }
        .dark .ls-hero, .dark .ls-panel, .dark .ls-kpi { border-color:#4a4540; background:#292623; }
        .dark .ls-hero { background:linear-gradient(135deg,#302c28,#242220); }
        .dark .ls-title, .dark .ls-panel-title, .dark .ls-source-name { color:#f3eee8; }
        .dark .ls-select, .dark .ls-all { border-color:#5b534c; background:#332f2c; color:#eee8e1; }
        .dark .ls-funnel-track, .dark .ls-bar-track { background:#46413d; }
        .dark .ls-donut:after { background:#292623; }
        .dark .ls-insight-note { background:#34302c; }
        @media (max-width:1200px) { .ls-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); } .ls-grid-3 { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:800px) { .ls-hero { align-items:stretch; flex-direction:column; } .ls-filter { width:100%; } .ls-field { flex:1; } .ls-select { width:100%; } .ls-grid-2, .ls-grid-3 { grid-template-columns:1fr; } }
        @media (max-width:560px) { .ls-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .ls-summary-strip { grid-template-columns:repeat(2,minmax(0,1fr)); } .ls-source-table th:nth-child(3), .ls-source-table td:nth-child(3) { display:none; } }
    </style>

    <div class="lead-stats">
        <section class="ls-hero">
            <div>
                <p class="ls-eyebrow">Lead intelligence · {{ $stats['period_label'] }}</p>
                <h1 class="ls-title">From inquiries to future strategy.</h1>
                <p class="ls-intro">Understand where demand comes from, which opportunities convert, and what couples are asking for. The year refers to the lead inquiry date.</p>
            </div>
            <div class="ls-filter">
                <div class="ls-field">
                    <label for="lead-stats-year">Inquiry year</label>
                    <select id="lead-stats-year" class="ls-select" wire:model.live="selectedYear">
                        @forelse ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @empty
                            <option value="{{ now()->year }}">{{ now()->year }}</option>
                        @endforelse
                    </select>
                </div>
                @if (count($years) > 1)
                    <button type="button" class="ls-all {{ $this->selectedYear === 'all' ? 'is-active' : '' }}" wire:click="showAllYears">All years</button>
                @endif
            </div>
        </section>

        <section class="ls-kpis">
            <article class="ls-kpi" style="--accent:#2E4A62;--accent-soft:rgba(46,74,98,.10)">
                <p class="ls-kpi-label">Inquiries</p><p class="ls-kpi-value">{{ number_format($stats['total']) }}</p><p class="ls-kpi-meta">New lead opportunities</p>
            </article>
            <article class="ls-kpi" style="--accent:#617563;--accent-soft:rgba(122,143,123,.14)">
                <p class="ls-kpi-label">Conversion</p><p class="ls-kpi-value">{{ number_format($stats['conversion_rate'], 1) }}%</p><p class="ls-kpi-meta">{{ $stats['converted'] }} projects or signed contracts</p>
            </article>
            <article class="ls-kpi" style="--accent:#9a7739;--accent-soft:rgba(201,169,106,.15)">
                <p class="ls-kpi-label">Proposal win rate</p><p class="ls-kpi-value">{{ number_format($stats['proposal_win_rate'], 1) }}%</p><p class="ls-kpi-meta">{{ $stats['converted'] }} won from {{ $stats['proposed'] }} proposals</p>
            </article>
            <article class="ls-kpi" style="--accent:#8d6157;--accent-soft:rgba(217,155,134,.14)">
                <p class="ls-kpi-label">Average budget</p><p class="ls-kpi-value">€ {{ number_format($stats['budget_average'], 0, ',', '.') }}</p><p class="ls-kpi-meta">Available on {{ number_format($stats['budget_coverage'], 1) }}% of leads</p>
            </article>
            <article class="ls-kpi" style="--accent:#715d70;--accent-soft:rgba(143,123,141,.13)">
                <p class="ls-kpi-label">Won planning fees</p><p class="ls-kpi-value">€ {{ number_format($stats['won_fees'], 0, ',', '.') }}</p><p class="ls-kpi-meta">€ {{ number_format($stats['quoted_fees'], 0, ',', '.') }} quoted overall</p>
            </article>
            <article class="ls-kpi" style="--accent:#73675e;--accent-soft:rgba(115,103,94,.11)">
                <p class="ls-kpi-label">Sales cycle</p><p class="ls-kpi-value">{{ $stats['average_sales_cycle'] ?? '—' }}{{ $stats['average_sales_cycle'] !== null ? ' d' : '' }}</p><p class="ls-kpi-meta">Average inquiry-to-contract time</p>
            </article>
        </section>

        <section class="ls-grid-2">
            <article class="ls-panel">
                <div class="ls-panel-head"><div><h2 class="ls-panel-title">Inquiry trend</h2><p class="ls-panel-copy">Monthly acquisition and converted leads.</p></div><span class="ls-pill">{{ $stats['period_label'] }}</span></div>
                <svg class="ls-trend" viewBox="0 0 720 235" role="img" aria-label="Monthly lead trend">
                    <defs><linearGradient id="leadTrendFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2E4A62" stop-opacity=".22"/><stop offset="1" stop-color="#2E4A62" stop-opacity="0"/></linearGradient></defs>
                    @for ($line = 0; $line < 4; $line++)
                        <line x1="38" y1="{{ 46 + ($line * 50) }}" x2="680" y2="{{ 46 + ($line * 50) }}" stroke="#e8e2db" stroke-width="1"/>
                    @endfor
                    <polygon points="38,196 {{ $trendPoints }} 680,196" fill="url(#leadTrendFill)"/>
                    <polyline points="{{ $trendPoints }}" fill="none" stroke="#2E4A62" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="{{ $conversionPoints }}" fill="none" stroke="#7A8F7B" stroke-width="3" stroke-dasharray="7 7" stroke-linecap="round" stroke-linejoin="round"/>
                    @foreach ($stats['monthly'] as $index => $month)
                        @php $x = 38 + ($index * (642 / 11)); @endphp
                        <circle cx="{{ $x }}" cy="{{ 196 - (($month['leads'] / $monthlyMax) * 150) }}" r="4" fill="#fff" stroke="#2E4A62" stroke-width="3"><title>{{ $month['label'] }}: {{ $month['leads'] }} leads</title></circle>
                        <text x="{{ $x }}" y="220" text-anchor="middle" class="ls-chart-label">{{ $month['label'] }}</text>
                    @endforeach
                </svg>
                <div class="ls-legend"><span><i class="ls-dot" style="background:#2E4A62"></i>Inquiries</span><span><i class="ls-dot" style="background:#7A8F7B"></i>Converted</span></div>
            </article>

            <article class="ls-panel">
                <div class="ls-panel-head"><div><h2 class="ls-panel-title">Commercial funnel</h2><p class="ls-panel-copy">How opportunities progress toward a booked event.</p></div></div>
                <div class="ls-funnel">
                    @foreach ($stats['funnel'] as $step)
                        <div class="ls-funnel-row"><span class="ls-funnel-label">{{ $step['label'] }}</span><div class="ls-funnel-track"><div class="ls-funnel-fill" style="width:{{ $step['rate'] }}%"></div></div><span class="ls-funnel-value">{{ $step['value'] }}</span></div>
                    @endforeach
                </div>
                <div class="ls-summary-strip">
                    <div class="ls-summary-item"><strong>{{ number_format($stats['qualification_rate'], 1) }}%</strong><span>qualified</span></div>
                    <div class="ls-summary-item"><strong>{{ number_format($stats['proposal_rate'], 1) }}%</strong><span>proposal rate</span></div>
                    <div class="ls-summary-item"><strong>{{ number_format($stats['lost_rate'], 1) }}%</strong><span>lost / rejected</span></div>
                    <div class="ls-summary-item"><strong>{{ number_format($stats['questionnaire_rate'], 1) }}%</strong><span>form completion</span></div>
                </div>
            </article>
        </section>

        <section class="ls-panel">
            <div class="ls-panel-head"><div><h2 class="ls-panel-title">Acquisition channel performance</h2><p class="ls-panel-copy">Volume alone can be misleading: compare each source with the leads it actually converts.</p></div></div>
            @if (count($stats['sources']))
                <div style="overflow-x:auto"><table class="ls-source-table"><thead><tr><th>Source</th><th>Leads</th><th>Converted</th><th>Share of demand</th><th>Conversion</th></tr></thead><tbody>
                    @foreach ($stats['sources'] as $source)
                        <tr><td class="ls-source-name">{{ $source['label'] }}</td><td>{{ $source['value'] }}</td><td>{{ $source['converted'] }}</td><td><div class="ls-source-meter"><div class="ls-bar-track"><div class="ls-bar-fill" style="width:{{ $stats['total'] ? ($source['value'] / $stats['total']) * 100 : 0 }}%"></div></div><span>{{ number_format($stats['total'] ? ($source['value'] / $stats['total']) * 100 : 0, 1) }}%</span></div></td><td><span class="ls-rate">{{ number_format($source['rate'], 1) }}%</span></td></tr>
                    @endforeach
                </tbody></table></div>
            @else
                <div class="ls-empty">No acquisition source data for this period.</div>
            @endif
        </section>

        <section class="ls-grid-3">
            <article class="ls-panel">
                <div class="ls-panel-head"><div><h2 class="ls-panel-title">Pipeline status</h2><p class="ls-panel-copy">Current distribution of the selected cohort.</p></div></div>
                @if (count($stats['statuses']))
                    <div class="ls-donut-wrap"><div class="ls-donut" style="background:conic-gradient({{ $donutStops }})"></div><div class="ls-legend-list">
                        @foreach ($stats['statuses'] as $index => $item)
                            <div class="ls-legend-row"><i class="ls-dot" style="background:{{ $statusColors[$index % count($statusColors)] }}"></i><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong></div>
                        @endforeach
                    </div></div>
                @else <div class="ls-empty">No status data.</div> @endif
            </article>

            @foreach ([['title' => 'Budget profile', 'copy' => 'Demand and conversion by estimated event budget.', 'items' => $stats['budget_bands'], 'conversion' => true], ['title' => 'Guest profile', 'copy' => 'Most common event sizes in the inquiry pipeline.', 'items' => $stats['guest_bands'], 'conversion' => false]] as $chart)
                <article class="ls-panel">
                    <div class="ls-panel-head"><div><h2 class="ls-panel-title">{{ $chart['title'] }}</h2><p class="ls-panel-copy">{{ $chart['copy'] }}</p></div></div>
                    <div class="ls-bars">
                        @foreach ($chart['items'] as $item)
                            <div class="ls-bar-row"><span class="ls-bar-label">{{ $item['label'] }}</span><div class="ls-bar-track"><div class="ls-bar-fill" style="width:{{ $item['rate'] }}%"></div></div><span class="ls-bar-value">{{ $item['value'] }}</span></div>
                            @if ($chart['conversion'] && $item['value'])<div style="margin:-.48rem 0 0 calc(33% + .65rem);color:#8b837b;font-size:.65rem">{{ number_format($item['conversion_rate'], 1) }}% converted</div>@endif
                        @endforeach
                    </div>
                    @if ($chart['title'] === 'Budget profile')<div class="ls-insight-note">Total declared event budgets: <strong>€ {{ number_format($stats['budget_total'], 0, ',', '.') }}</strong>.</div>@endif
                </article>
            @endforeach
        </section>

        <section class="ls-grid-3">
            @foreach ([['title' => 'Event types', 'copy' => 'Which services are entering the pipeline.', 'items' => $stats['event_types']], ['title' => 'Requested destinations', 'copy' => 'Top regions couples are considering.', 'items' => $stats['regions']], ['title' => 'Origin markets', 'copy' => 'Country, falling back to nationality.', 'items' => $stats['origins']]] as $chart)
                <article class="ls-panel">
                    <div class="ls-panel-head"><div><h2 class="ls-panel-title">{{ $chart['title'] }}</h2><p class="ls-panel-copy">{{ $chart['copy'] }}</p></div></div>
                    @if (count($chart['items']))<div class="ls-bars">@foreach ($chart['items'] as $item)<div class="ls-bar-row"><span class="ls-bar-label" title="{{ $item['label'] }}">{{ $item['label'] }}</span><div class="ls-bar-track"><div class="ls-bar-fill" style="width:{{ $item['rate'] }}%"></div></div><span class="ls-bar-value">{{ $item['value'] }}</span></div>@endforeach</div>@else<div class="ls-empty">Not enough data yet.</div>@endif
                </article>
            @endforeach
        </section>

        <section class="ls-grid-3">
            @foreach ([['title' => 'Ceremony preference', 'copy' => 'Religious, civil or symbolic demand.', 'items' => $stats['ceremonies']], ['title' => 'Venue requirement', 'copy' => 'Stay and event versus event-only demand.', 'items' => $stats['venue_requests']], ['title' => 'How couples discovered you', 'copy' => 'Attribution declared in completed questionnaires.', 'items' => $stats['discovery_sources']]] as $chart)
                <article class="ls-panel">
                    <div class="ls-panel-head"><div><h2 class="ls-panel-title">{{ $chart['title'] }}</h2><p class="ls-panel-copy">{{ $chart['copy'] }}</p></div></div>
                    @if (count($chart['items']))<div class="ls-bars">@foreach ($chart['items'] as $item)<div class="ls-bar-row"><span class="ls-bar-label" title="{{ $item['label'] }}">{{ $item['label'] }}</span><div class="ls-bar-track"><div class="ls-bar-fill" style="width:{{ $item['rate'] }}%"></div></div><span class="ls-bar-value">{{ $item['value'] }}</span></div>@endforeach</div>@else<div class="ls-empty">Not enough data yet.</div>@endif
                </article>
            @endforeach
        </section>

        <section class="ls-grid-2">
            @foreach ([['title' => 'Services couples value most', 'copy' => 'Top priorities selected in the questionnaire.', 'items' => $stats['priorities']], ['title' => 'Most desired venue styles', 'copy' => 'Venue types selected in the questionnaire.', 'items' => $stats['venue_types']]] as $chart)
                <article class="ls-panel">
                    <div class="ls-panel-head"><div><h2 class="ls-panel-title">{{ $chart['title'] }}</h2><p class="ls-panel-copy">{{ $chart['copy'] }}</p></div></div>
                    @if (count($chart['items']))<div class="ls-bars">@foreach ($chart['items'] as $item)<div class="ls-bar-row"><span class="ls-bar-label" title="{{ $item['label'] }}">{{ $item['label'] }}</span><div class="ls-bar-track"><div class="ls-bar-fill" style="width:{{ $item['rate'] }}%"></div></div><span class="ls-bar-value">{{ $item['value'] }}</span></div>@endforeach</div>@else<div class="ls-empty">Questionnaire data is not available for this period.</div>@endif
                </article>
            @endforeach
        </section>

        <div class="ls-insight-note">Operational context: {{ $stats['questionnaires_completed'] }} of {{ $stats['questionnaires_sent'] }} sent questionnaires were completed, and {{ number_format($stats['follow_up_completion_rate'], 1) }}% of recorded follow-ups are completed. Percentages are calculated only where the underlying field is available.</div>
    </div>
</x-filament-panels::page>
