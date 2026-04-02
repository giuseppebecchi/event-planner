<x-filament-panels::page>
    <style>
        .wm-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .wm-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(0, 0.9fr);
            gap: 1rem;
            align-items: stretch;
        }

        .wm-hero-card,
        .wm-panel {
            border: 1px solid #e8e3dc;
            border-radius: 1.4rem;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 18px 40px rgba(45, 42, 38, 0.06);
        }

        .wm-hero-main {
            padding: 1.5rem 1.6rem;
            background:
                radial-gradient(circle at top left, rgba(242, 198, 160, 0.26), transparent 34%),
                radial-gradient(circle at right center, rgba(122, 143, 123, 0.2), transparent 36%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(247, 243, 237, 0.96));
        }

        .wm-eyebrow {
            margin: 0 0 0.55rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #c9a96a;
        }

        .wm-hero-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.6rem, 2.6vw, 2.4rem);
            line-height: 1.08;
            color: #2d2a26;
        }

        .wm-hero-copy {
            max-width: 42rem;
            margin: 0.9rem 0 0;
            font-size: 0.95rem;
            line-height: 1.7;
            color: #746d66;
        }

        .wm-hero-side {
            padding: 1.35rem 1.4rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                linear-gradient(160deg, rgba(46, 74, 98, 0.96), rgba(36, 60, 81, 0.96));
            color: #f8f4ef;
        }

        .wm-hero-side-title {
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
        }

        .wm-hero-side-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .wm-hero-side-item {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .wm-hero-side-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .wm-hero-side-label {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .wm-hero-side-value {
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.74);
        }

        .wm-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-stat {
            padding: 1.15rem 1.2rem;
            position: relative;
            overflow: hidden;
        }

        .wm-stat::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.35rem;
            background: var(--tone, #7a8f7b);
        }

        .wm-stat-label {
            margin: 0;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #8b847d;
        }

        .wm-stat-head,
        .wm-panel-heading {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .wm-icon-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .wm-icon-chip svg {
            width: 1.15rem;
            height: 1.15rem;
        }

        .wm-icon-chip.olive {
            background: rgba(122, 143, 123, 0.16);
            color: #617563;
        }

        .wm-icon-chip.blue {
            background: rgba(46, 74, 98, 0.12);
            color: #2e4a62;
        }

        .wm-icon-chip.gold {
            background: rgba(201, 169, 106, 0.16);
            color: #9a7a39;
        }

        .wm-icon-chip.rose {
            background: rgba(227, 183, 178, 0.22);
            color: #9c6f6b;
        }

        .wm-stat-value {
            margin: 0.55rem 0 0.35rem;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            color: #2d2a26;
        }

        .wm-stat-caption {
            margin: 0;
            font-size: 0.86rem;
            line-height: 1.55;
            color: #746d66;
        }

        .wm-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 1rem;
        }

        .wm-stack {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-panel {
            padding: 1.15rem 1.2rem;
        }

        .wm-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .wm-panel-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 1.04rem;
            color: #2d2a26;
        }

        .wm-panel-subtitle {
            margin: 0.35rem 0 0;
            font-size: 0.83rem;
            line-height: 1.55;
            color: #8b847d;
        }

        .wm-link {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #2e4a62;
            text-decoration: none;
        }

        .wm-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .wm-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.9rem;
            align-items: start;
            padding: 0.95rem 1rem;
            border: 1px solid #eee7e0;
            border-radius: 1rem;
            background: #fffdfa;
            text-decoration: none;
        }

        .wm-item:hover {
            border-color: #d9c4ab;
            background: #fffaf4;
        }

        .wm-item-title {
            margin: 0;
            font-size: 0.96rem;
            font-weight: 700;
            color: #2d2a26;
        }

        .wm-item-meta,
        .wm-item-copy {
            margin: 0.28rem 0 0;
            font-size: 0.84rem;
            line-height: 1.55;
            color: #756f68;
        }

        .wm-item-aside {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
            min-width: 7.5rem;
        }

        .wm-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.35rem 0.62rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .wm-badge.olive { background: rgba(122, 143, 123, 0.16); color: #617563; }
        .wm-badge.blue { background: rgba(46, 74, 98, 0.12); color: #2e4a62; }
        .wm-badge.gold { background: rgba(201, 169, 106, 0.16); color: #9a7a39; }
        .wm-badge.rose { background: rgba(227, 183, 178, 0.22); color: #9c6f6b; }

        .wm-item-note {
            font-size: 0.79rem;
            color: #9a938c;
            text-align: right;
        }

        .wm-deadlines {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .wm-deadline {
            border: 1px solid #eee7e0;
            border-radius: 1rem;
            padding: 0.95rem 1rem;
            background: #fffdfa;
        }

        .wm-empty {
            padding: 1rem;
            border: 1px dashed #d8cdc1;
            border-radius: 1rem;
            color: #8c857e;
            background: rgba(255, 255, 255, 0.55);
            font-size: 0.88rem;
        }

        @media (max-width: 1200px) {
            .wm-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .wm-grid,
            .wm-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .wm-stats,
            .wm-deadlines {
                grid-template-columns: 1fr;
            }

            .wm-item {
                grid-template-columns: 1fr;
            }

            .wm-item-aside {
                align-items: flex-start;
                min-width: 0;
            }
        }
    </style>

    <div class="wm-dashboard">
        <section class="wm-hero">
            <div class="wm-hero-card wm-hero-main">
                <p class="wm-eyebrow">Planning cockpit</p>
                <h1 class="wm-hero-title">Keep hot leads and active weddings in view.</h1>
                <p class="wm-hero-copy">
                    The dashboard is organized to surface what needs attention first: promising leads, active projects, confirmed upcoming events, operational deadlines, and next follow ups.
                </p>
            </div>

            <div class="wm-hero-card wm-hero-side">
                <div>
                    <h2 class="wm-hero-side-title">Today’s focus</h2>
                    <div class="wm-hero-side-list">
                        <div class="wm-hero-side-item">
                            <div class="wm-hero-side-label">Review warm conversations</div>
                            <div class="wm-hero-side-value">{{ count($hotLeads) }} leads ready</div>
                        </div>
                        <div class="wm-hero-side-item">
                            <div class="wm-hero-side-label">Check production status</div>
                            <div class="wm-hero-side-value">{{ count($projectsInPreparation) }} active projects</div>
                        </div>
                        <div class="wm-hero-side-item">
                            <div class="wm-hero-side-label">Prepare upcoming events</div>
                            <div class="wm-hero-side-value">{{ count($upcomingConfirmedEvents) }} confirmed soon</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="wm-stats">
            @foreach($stats as $stat)
                <article class="wm-hero-card wm-stat" style="--tone:
                    {{ $stat['tone'] === 'blue' ? '#2E4A62' : ($stat['tone'] === 'gold' ? '#C9A96A' : ($stat['tone'] === 'rose' ? '#E3B7B2' : '#7A8F7B')) }};">
                    <div class="wm-stat-head">
                        <span class="wm-icon-chip {{ $stat['tone'] }}">
                            <x-filament::icon :icon="$stat['icon']" />
                        </span>
                        <p class="wm-stat-label">{{ $stat['label'] }}</p>
                    </div>
                    <p class="wm-stat-value">{{ $stat['value'] }}</p>
                    <p class="wm-stat-caption">{{ $stat['caption'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="wm-grid">
            <div class="wm-stack">
                <article class="wm-panel">
                    <div class="wm-panel-header">
                        <div class="wm-panel-heading">
                            <span class="wm-icon-chip olive">
                                <x-filament::icon icon="heroicon-o-fire" />
                            </span>
                            <div>
                                <h2 class="wm-panel-title">Hot leads</h2>
                                <p class="wm-panel-subtitle">Leads with active momentum and immediate commercial relevance.</p>
                            </div>
                        </div>
                        <a class="wm-link" href="{{ \App\Filament\Resources\LeadResource::getUrl() }}">All leads</a>
                    </div>

                    <div class="wm-list">
                        @forelse($hotLeads as $lead)
                            <a class="wm-item" href="{{ $lead['url'] }}">
                                <div>
                                    <p class="wm-item-title">{{ $lead['name'] }}</p>
                                    <p class="wm-item-meta">{{ $lead['region'] }} · {{ $lead['weddingPeriod'] }}</p>
                                    <p class="wm-item-copy">
                                        {{ $lead['guestCount'] ? $lead['guestCount'] . ' guests expected' : 'Guest count to define' }}
                                        @if($lead['pendingFollowUps'])
                                            · {{ $lead['pendingFollowUps'] }} pending follow ups
                                        @endif
                                    </p>
                                </div>
                                <div class="wm-item-aside">
                                    <span class="wm-badge olive">{{ $lead['status'] }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="wm-empty">No hot leads at the moment.</div>
                        @endforelse
                    </div>
                </article>

                <article class="wm-panel">
                    <div class="wm-panel-header">
                        <div class="wm-panel-heading">
                            <span class="wm-icon-chip blue">
                                <x-filament::icon icon="heroicon-o-folder-open" />
                            </span>
                            <div>
                                <h2 class="wm-panel-title">Projects in preparation</h2>
                                <p class="wm-panel-subtitle">Weddings being built, coordinated, or finalized right now.</p>
                            </div>
                        </div>
                        <a class="wm-link" href="{{ \App\Filament\Resources\ProjectResource::getUrl() }}">All projects</a>
                    </div>

                    <div class="wm-list">
                        @forelse($projectsInPreparation as $project)
                            <a class="wm-item" href="{{ $project['url'] }}">
                                <div>
                                    <p class="wm-item-title">{{ $project['name'] }}</p>
                                    <p class="wm-item-meta">{{ $project['couple'] }}</p>
                                    <p class="wm-item-copy">{{ $project['place'] }} · {{ $project['date'] }}</p>
                                </div>
                                <div class="wm-item-aside">
                                    <span class="wm-badge blue">{{ $project['status'] }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="wm-empty">No active projects in preparation.</div>
                        @endforelse
                    </div>
                </article>
            </div>

            <div class="wm-stack">
                <article class="wm-panel">
                    <div class="wm-panel-header">
                        <div class="wm-panel-heading">
                            <span class="wm-icon-chip gold">
                                <x-filament::icon icon="heroicon-o-calendar-days" />
                            </span>
                            <div>
                                <h2 class="wm-panel-title">Upcoming confirmed events</h2>
                                <p class="wm-panel-subtitle">Confirmed celebrations approaching soon.</p>
                            </div>
                        </div>
                    </div>

                    <div class="wm-list">
                        @forelse($upcomingConfirmedEvents as $event)
                            <a class="wm-item" href="{{ $event['url'] }}">
                                <div>
                                    <p class="wm-item-title">{{ $event['name'] }}</p>
                                    <p class="wm-item-meta">{{ $event['couple'] }}</p>
                                    <p class="wm-item-copy">{{ $event['place'] }} · {{ $event['guests'] ? $event['guests'] . ' guests' : 'Guest list to define' }}</p>
                                </div>
                                <div class="wm-item-aside">
                                    <span class="wm-badge gold">{{ $event['date'] }}</span>
                                    <div class="wm-item-note">{{ $event['days'] }} days</div>
                                </div>
                            </a>
                        @empty
                            <div class="wm-empty">No confirmed upcoming events found.</div>
                        @endforelse
                    </div>
                </article>

                <article class="wm-panel">
                    <div class="wm-panel-header">
                        <div class="wm-panel-heading">
                            <span class="wm-icon-chip rose">
                                <x-filament::icon icon="heroicon-o-banknotes" />
                            </span>
                            <div>
                                <h2 class="wm-panel-title">Deadlines</h2>
                                <p class="wm-panel-subtitle">Temporary sample reminders for payments and couple communications.</p>
                            </div>
                        </div>
                    </div>

                    <div class="wm-deadlines">
                        @foreach($fakeDeadlines as $deadline)
                            <div class="wm-deadline">
                                <span class="wm-badge {{ $deadline['tone'] }}">{{ $deadline['kind'] }}</span>
                                <p class="wm-item-title" style="margin-top: .75rem;">{{ $deadline['title'] }}</p>
                                <p class="wm-item-meta">{{ $deadline['context'] }}</p>
                                <p class="wm-item-copy">Due {{ $deadline['due'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="wm-panel">
                    <div class="wm-panel-header">
                        <div class="wm-panel-heading">
                            <span class="wm-icon-chip blue">
                                <x-filament::icon icon="heroicon-o-chat-bubble-left-right" />
                            </span>
                            <div>
                                <h2 class="wm-panel-title">Upcoming appointments / follow up</h2>
                                <p class="wm-panel-subtitle">Next planned calls, reminders, and touchpoints.</p>
                            </div>
                        </div>
                    </div>

                    <div class="wm-list">
                        @forelse($upcomingFollowUps as $followUp)
                            <a class="wm-item" href="{{ $followUp['url'] }}">
                                <div>
                                    <p class="wm-item-title">{{ $followUp['subject'] }}</p>
                                    <p class="wm-item-meta">{{ $followUp['lead'] }}</p>
                                    <p class="wm-item-copy">{{ $followUp['type'] }} · {{ $followUp['dueAt'] }}</p>
                                </div>
                                <div class="wm-item-aside">
                                    <span class="wm-badge rose">{{ $followUp['priority'] }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="wm-empty">No pending follow ups scheduled.</div>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-filament-panels::page>
