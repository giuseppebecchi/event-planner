<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $project->name }} Timeline</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #231d18; font-size: 11px; line-height: 1.55; background: #f8f4ee; }
        h1, h2, h3, p { margin: 0; }
        .page { position: relative; width: 100%; page-break-after: always; background: #f7f3ed; overflow: hidden; }
        .page:last-child { page-break-after: auto; }
        .page-shell { position: relative; padding-left: 232px; }
        .left-rail { position: absolute; inset: 0 auto 0 0; width: 232px; background: #e9dfd3; }
        .left-rail.has-image { background-size: cover; background-position: center; }
        .left-rail-overlay { position: absolute; inset: 0; background: rgba(248, 243, 237, 0.14); }
        .left-rail-copy { position: absolute; left: 28px; right: 24px; bottom: 36px; color: rgba(255, 255, 255, 0.94); font-size: 9px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; }
        .page-main { position: relative; padding: 34px 48px 42px 34px; background: #fcfaf6; min-height: 1046px; }
        .corner-flower-top, .corner-flower-bottom { position: absolute; background: radial-gradient(circle at center, rgba(230, 188, 179, 0.85) 0, rgba(230, 188, 179, 0.55) 42%, rgba(230, 188, 179, 0) 72%); pointer-events: none; }
        .corner-flower-top { top: -28px; right: -18px; width: 180px; height: 180px; }
        .corner-flower-bottom { right: -34px; bottom: -34px; width: 170px; height: 170px; background: radial-gradient(circle at center, rgba(208, 228, 220, 0.95) 0, rgba(208, 228, 220, 0.45) 44%, rgba(208, 228, 220, 0) 76%); }
        .headline-serif { font-family: DejaVu Serif, serif; }
        .hero-name { max-width: 500px; color: #1a1410; font-size: 42px; line-height: 0.94; font-weight: 700; letter-spacing: 0.01em; }
        .hero-meta { margin-top: 18px; color: #211c17; font-size: 18px; letter-spacing: 0.28em; text-transform: uppercase; }
        .hero-submeta { margin-top: 4px; color: #211c17; font-size: 13px; letter-spacing: 0.22em; text-transform: uppercase; }
        .hero-guests { margin-top: 10px; color: #4d433a; font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase; }
        .editorial-label { margin-top: 112px; color: #231d18; font-size: 14px; font-style: italic; letter-spacing: 0.12em; text-transform: uppercase; }
        .cover-timeline { margin-top: 18px; width: 100%; border-collapse: collapse; }
        .cover-timeline td { vertical-align: top; }
        .cover-time-col { width: 230px; padding-right: 24px; }
        .cover-divider-col { width: 28px; position: relative; }
        .cover-divider { position: absolute; left: 13px; top: 4px; bottom: 10px; width: 2px; background: #26201b; }
        .cover-activity-col { padding-left: 18px; padding-right: 34px; }
        .cover-time { height: 98px; color: #1f1914; font-size: 23px; letter-spacing: 0.08em; text-transform: uppercase; text-align: right; white-space: nowrap; }
        .cover-activity { height: 98px; }
        .cover-activity-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .cover-activity-icon { width: 82px; text-align: center; vertical-align: middle; }
        .cover-activity-icon img { max-width: 60px; max-height: 62px; }
        .cover-activity-text { vertical-align: middle; }
        .cover-activity-label { color: #1f1914; font-size: 20px; letter-spacing: 0.12em; text-transform: uppercase; }
        .cover-activity-detail { margin-top: 4px; color: #6d645a; font-size: 10px; }
        .section-band { display: inline-block; margin-bottom: 16px; padding: 10px 16px 8px; background: #efcbbb; color: #261e18; font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; }
        .section-copy { margin-bottom: 24px; color: #74695f; font-size: 11px; }
        .timeline-day { margin-bottom: 28px; page-break-inside: avoid; }
        .timeline-day-title { color: #1f1914; font-size: 18px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        .timeline-day-meta { margin-top: 4px; color: #81766c; font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; }
        .daily-note { margin-top: 14px; margin-bottom: 12px; padding: 12px 14px; border-left: 3px solid #c7a56a; background: #fff8f0; }
        .daily-note-title { color: #8f713b; font-size: 9px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; }
        .daily-note-text { margin-top: 4px; color: #433b33; }
        .item { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; }
        .item-time { width: 104px; padding: 3px 14px 0 0; text-align: right; vertical-align: top; color: #4d4339; font-size: 12px; font-weight: 700; }
        .item-time-range { display: block; margin-top: 1px; color: #91867b; font-size: 9px; font-weight: 400; }
        .item-body { padding-left: 16px; border-left: 2px solid #c7a56a; vertical-align: top; }
        .item-title { color: #1e1813; font-size: 13px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .item-tags { margin-top: 6px; font-size: 9px; color: #766b61; }
        .item-tag { display: inline-block; margin: 0 6px 4px 0; padding: 2px 7px; background: #f2ece4; }
        .item-text { margin-top: 7px; color: #463f37; }
        .item-images { margin-top: 9px; font-size: 0; }
        .item-image { display: inline-block; width: 84px; height: 84px; margin: 0 8px 8px 0; overflow: hidden; background: #efe7dc; }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        .detail-card { padding: 0 8px 0 0; page-break-inside: avoid; }
        .detail-card + .detail-card { margin-top: 24px; }
        .detail-title { color: #1f1914; font-size: 22px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        .detail-meta { margin-top: 10px; color: #6f665c; font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; }
        .detail-html { margin-top: 26px; color: #302923; font-size: 12px; line-height: 1.78; }
        .recap-card { margin-top: 16px; padding-bottom: 16px; border-bottom: 1px solid #e8ded1; page-break-inside: avoid; }
        .recap-title { color: #1f1914; font-size: 13px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }
        .recap-meta { margin-top: 4px; color: #8c8176; font-size: 9px; letter-spacing: 0.08em; text-transform: uppercase; }
        .recap-html { margin-top: 9px; color: #302923; font-size: 11px; line-height: 1.65; }
        .suppliers { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .suppliers th { padding: 10px 8px; background: #f1e5d9; color: #342d26; font-size: 8px; letter-spacing: 0.14em; text-transform: uppercase; text-align: left; }
        .suppliers td { padding: 11px 8px; border-bottom: 1px solid #e8ded1; vertical-align: top; color: #4b433b; }
        .supplier-category { color: #a98a52; font-size: 9px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; }
        .supplier-name { color: #1f1914; font-weight: 700; }
        .muted { color: #8c8176; }
        .empty { color: #8b8175; font-style: italic; }
    </style>
</head>
<body>
    <section class="page">
        <div class="page-shell">
            <aside class="left-rail {{ $leftRailImage ? 'has-image' : '' }}" @if ($leftRailImage) style="background-image: url('{{ $leftRailImage }}')" @endif>
                <div class="left-rail-overlay"></div>
            </aside>

            <div class="page-main">
                <div class="corner-flower-top"></div>
                <div class="corner-flower-bottom"></div>

                <h1 class="hero-name headline-serif">{{ $project->name }}</h1>
                <p class="hero-meta">{{ $dateRange }}</p>
                @if ($location)
                    <p class="hero-submeta">{{ $location }}</p>
                @endif
                @if ($project->final_guest_count || $project->estimated_guest_count)
                    <p class="hero-guests">{{ $project->final_guest_count ?: $project->estimated_guest_count }} guests</p>
                @endif

                <p class="editorial-label">Wedding Day Timeline And Info</p>

                @if ($coverActivities->isNotEmpty())
                    <table class="cover-timeline">
                        <tr>
                            <td class="cover-time-col">
                                @foreach ($coverActivities as $activity)
                                    <div class="cover-time">
                                        {{ $activity['start_time'] ?: ($activity['end_time'] ?: '-') }}
                                    </div>
                                @endforeach
                            </td>
                            <td class="cover-divider-col">
                                <div class="cover-divider"></div>
                            </td>
                            <td class="cover-activity-col">
                                @foreach ($coverActivities as $activity)
                                    <div class="cover-activity">
                                        <table class="cover-activity-table">
                                            <tr>
                                                <td class="cover-activity-icon">
                                                    @if ($activity['icon'])
                                                        <img src="{{ $activity['icon'] }}" alt="">
                                                    @endif
                                                </td>
                                                <td class="cover-activity-text">
                                                    <div class="cover-activity-label">{{ $activity['cover_activity_type'] ?: $activity['title'] }}</div>
                                                    @if ($activity['location'] || $activity['location_plan_b'] || $activity['supplier_name'])
                                                        <div class="cover-activity-detail">{{ collect([$activity['location'], $activity['location_plan_b'] ? 'Plan B: ' . $activity['location_plan_b'] : null, $activity['supplier_name']])->filter()->implode(' | ') }}</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    </table>
                @else
                    <p class="empty" style="margin-top: 30px;">No cover activities selected yet.</p>
                @endif
            </div>
        </div>
    </section>

    @forelse ($days as $day)
        <section class="page">
            <div class="page-shell">
                <aside class="left-rail {{ $leftRailImage ? 'has-image' : '' }}" @if ($leftRailImage) style="background-image: url('{{ $leftRailImage }}')" @endif>
                    <div class="left-rail-overlay"></div>
                    <div class="left-rail-copy">{{ $day['date']->format('F j, Y') }}</div>
                </aside>

                <div class="page-main">
                    <div class="corner-flower-bottom"></div>
                    <div class="section-band">Detailed timeline</div>
                    <section class="timeline-day">
                        <h2 class="timeline-day-title">{{ $day['date']->format('l, F j, Y') }}</h2>
                        <p class="timeline-day-meta">
                            {{ count($day['items']) }} items
                            @if ($day['sunset_time'])
                                | Sunset {{ $day['sunset_time']->format('H:i') }}
                            @endif
                        </p>

                        @if (! empty($day['daily_note_description']))
                            <div class="daily-note">
                                <p class="daily-note-title">Daily notes</p>
                                <p class="daily-note-text">{{ $day['daily_note_description'] }}</p>
                            </div>
                        @endif

                        @forelse ($day['items'] as $item)
                            <table class="item">
                                <tr>
                                    <td class="item-time">
                                        {{ $item['start_time'] ?: '-' }}
                                        @if ($item['end_time'])
                                            <span class="item-time-range">to {{ $item['end_time'] }}</span>
                                        @endif
                                    </td>
                                    <td class="item-body">
                                        <h3 class="item-title">{{ $item['title'] }}</h3>

                                        @if ($item['location'] || $item['location_plan_b'] || $item['supplier_name'] || $item['is_surprise'] || $item['cover_activity'] || $item['sunset_time'])
                                            <div class="item-tags">
                                                @if ($item['location'])
                                                    <span class="item-tag">{{ $item['location'] }}</span>
                                                @endif
                                                @if ($item['location_plan_b'])
                                                    <span class="item-tag">Plan B: {{ $item['location_plan_b'] }}</span>
                                                @endif
                                                @if ($item['supplier_name'])
                                                    <span class="item-tag">{{ $item['supplier_name'] }}</span>
                                                @endif
                                                @if ($item['is_surprise'])
                                                    <span class="item-tag">Surprise</span>
                                                @endif
                                                @if ($item['cover_activity'])
                                                    <span class="item-tag">Cover {{ $item['cover_activity_type'] }}</span>
                                                @endif
                                                @if ($item['sunset_time'])
                                                    <span class="item-tag">Sunset {{ $item['sunset_time'] }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($item['description'])
                                            <div class="item-text">{{ $item['description'] }}</div>
                                        @endif

                                        @if ($item['notes'])
                                            <div class="item-text"><strong>Notes:</strong> {{ $item['notes'] }}</div>
                                        @endif

                                        @if (! empty($item['images']))
                                            <div class="item-images">
                                                @foreach ($item['images'] as $image)
                                                    <span class="item-image"><img src="{{ $image }}" alt=""></span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        @empty
                            <p class="empty" style="margin-top: 12px;">No timeline items for this day yet.</p>
                        @endforelse
                    </section>

                    @if (! empty($day['extended_items']))
                        <div class="section-band" style="margin-top: 20px;">Day details</div>

                        @foreach ($day['extended_items'] as $activity)
                            <div class="detail-card">
                                <h2 class="detail-title">{{ $activity['title'] }}</h2>
                                <p class="detail-meta">
                                    @if ($activity['start_time'])
                                        {{ $activity['start_time'] }}@if ($activity['end_time']) - {{ $activity['end_time'] }}@endif
                                    @endif
                                    @if ($activity['supplier_name'])
                                        | {{ $activity['supplier_name'] }}
                                    @endif
                                    @if ($activity['location'])
                                        | {{ $activity['location'] }}
                                    @endif
                                    @if ($activity['location_plan_b'])
                                        | Plan B: {{ $activity['location_plan_b'] }}
                                    @endif
                                </p>

                                <div class="detail-html">{!! $activity['extended_description'] !!}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </section>
    @empty
        <section class="page">
            <div class="page-shell">
                <aside class="left-rail {{ $leftRailImage ? 'has-image' : '' }}" @if ($leftRailImage) style="background-image: url('{{ $leftRailImage }}')" @endif>
                    <div class="left-rail-overlay"></div>
                    <div class="left-rail-copy">Timeline recap</div>
                </aside>

                <div class="page-main">
                    <div class="corner-flower-bottom"></div>
                    <div class="section-band">Detailed timeline</div>
                    <p class="empty">No timeline days available for this project.</p>
                </div>
            </div>
        </section>
    @endforelse

    @if (($recapChecklistItems ?? collect())->isNotEmpty())
        <section class="page">
            <div class="page-shell">
                <aside class="left-rail {{ $leftRailImage ? 'has-image' : '' }}" @if ($leftRailImage) style="background-image: url('{{ $leftRailImage }}')" @endif>
                    <div class="left-rail-overlay"></div>
                    <div class="left-rail-copy">Checklist recap</div>
                </aside>

                <div class="page-main">
                    <div class="corner-flower-bottom"></div>
                    <div class="section-band">Checklist recap</div>
                    <p class="section-copy">Texts selected from project checklist compilations.</p>

                    @foreach ($recapChecklistItems as $item)
                        <div class="recap-card">
                            <h3 class="recap-title">{!! $item['title'] !!}</h3>
                            <p class="recap-meta">
                                {{ collect([$item['supplier_name'], $item['due_date']])->filter()->implode(' | ') ?: 'Project checklist' }}
                            </p>
                            @if ($item['response'])
                                <div class="recap-html">{!! $item['response'] !!}</div>
                            @elseif ($item['details'])
                                <div class="recap-html">{!! $item['details'] !!}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="page">
        <div class="page-shell">
            <aside class="left-rail {{ $leftRailImage ? 'has-image' : '' }}" @if ($leftRailImage) style="background-image: url('{{ $leftRailImage }}')" @endif>
                <div class="left-rail-overlay"></div>
                <div class="left-rail-copy">Confirmed suppliers</div>
            </aside>

            <div class="page-main">
                <div class="corner-flower-bottom"></div>
                <div class="section-band">Confirmed suppliers</div>
                <p class="section-copy">Operational contacts and vendor references for the event day.</p>

                @if ($confirmedSuppliers->isNotEmpty())
                    <table class="suppliers">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Contact</th>
                                <th>Address / web</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($confirmedSuppliers as $supplier)
                                <tr>
                                    <td><span class="supplier-category">{{ $supplier['category'] }}</span></td>
                                    <td>
                                        <div class="supplier-name">{{ $supplier['name'] }}</div>
                                        @if ($supplier['confirmed_at'])
                                            <div class="muted">Confirmed {{ $supplier['confirmed_at'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($supplier['contact_person'])
                                            <div>{{ $supplier['contact_person'] }}</div>
                                        @endif
                                        @if ($supplier['email'])
                                            <div>{{ $supplier['email'] }}</div>
                                        @endif
                                        @if ($supplier['phone'])
                                            <div>{{ $supplier['phone'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($supplier['address'])
                                            <div>{{ $supplier['address'] }}</div>
                                        @endif
                                        @if ($supplier['website'])
                                            <div>{{ $supplier['website'] }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="empty">No confirmed suppliers yet.</p>
                @endif
            </div>
        </div>
    </section>
</body>
</html>
