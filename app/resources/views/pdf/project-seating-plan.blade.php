<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 24px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #2d2a26; font-size: 12px; }
        .cover { padding-top: 135px; text-align: center; page-break-after: always; }
        .eyebrow { color: #8b8279; font-size: 11px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
        h1 { margin: 18px 0 8px; font-size: 34px; line-height: 1.15; }
        h2 { margin: 0 0 16px; font-size: 20px; }
        .meta { color: #6c645d; font-size: 13px; }
        .stats { margin: 38px auto 0; width: 470px; border-collapse: collapse; }
        .stats td { padding: 13px 12px; border: 1px solid #e5d9cd; }
        .stat-label { color: #8b8279; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { margin-top: 4px; font-size: 22px; font-weight: bold; }
        .notes { margin: 26px auto 0; width: 620px; padding: 14px 16px; border: 1px solid #e5d9cd; background: #fffdf9; text-align: left; line-height: 1.45; }
        .notes-title { margin-bottom: 7px; color: #8b8279; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .page { page-break-after: always; }
        .map-frame { border: 1px solid #e5d9cd; padding: 10px; background: #faf7f2; }
        .map-frame img { width: 100%; height: auto; display: block; }
        .empty-map { height: 390px; display: table; width: 100%; color: #8b8279; text-align: center; }
        .empty-map span { display: table-cell; vertical-align: middle; }
        .tables-grid { width: 100%; border-collapse: separate; border-spacing: 7px 8px; }
        .tables-grid-cell { width: 33.333%; vertical-align: top; }
        .table-card { padding: 7px 8px; border: 1px solid #e5d9cd; border-radius: 7px; page-break-inside: avoid; }
        .table-head { width: 100%; margin-bottom: 4px; border-collapse: collapse; }
        .table-name { font-size: 11px; font-weight: bold; white-space: nowrap; overflow: hidden; }
        .table-count { color: #6f8f64; text-align: right; font-size: 8px; font-weight: bold; white-space: nowrap; }
        .seat-list { width: 100%; border-collapse: collapse; }
        .seat-list td { padding: 2px 3px; border-top: 1px solid #f0e8df; vertical-align: top; line-height: 1.15; }
        .seat-number { width: 25px; color: #6f5830; font-size: 8px; font-weight: bold; white-space: nowrap; }
        .guest-name { font-size: 8.5px; white-space: nowrap; }
        .guest-group { color: #8b8279; font-size: 7px; white-space: nowrap; overflow: hidden; }
        .empty-seat { color: #9a9289; font-size: 8px; white-space: nowrap; }
    </style>
</head>
<body>
    <section class="cover">
        <div class="eyebrow">Seating Plan</div>
        <h1>{{ $seatingPlan->name }}</h1>
        <div class="meta">{{ $project->name }} · {{ now()->format('d/m/Y') }}</div>

        <table class="stats">
            <tr>
                <td>
                    <div class="stat-label">Tables</div>
                    <div class="stat-value">{{ $stats['tables'] }}</div>
                </td>
                <td>
                    <div class="stat-label">Seats</div>
                    <div class="stat-value">{{ $stats['seats'] }}</div>
                </td>
                <td>
                    <div class="stat-label">Assigned</div>
                    <div class="stat-value">{{ $stats['assigned'] }}</div>
                </td>
            </tr>
        </table>

        @if (filled($seatingPlan->notes))
            <div class="notes">
                <div class="notes-title">Notes</div>
                {!! nl2br(e($seatingPlan->notes)) !!}
            </div>
        @endif
    </section>

    <section class="page">
        <h2>Map</h2>
        <div class="map-frame">
            <img src="{{ $mapDataUri }}" alt="Seating plan map">
        </div>
    </section>

    <section>
        <h2>Guests by table</h2>
        <table class="tables-grid">
            @foreach ($tables->chunk(3) as $row)
                <tr>
                    @foreach ($row as $table)
                        <td class="tables-grid-cell">
                            <article class="table-card">
                                <table class="table-head">
                                    <tr>
                                        <td class="table-name">{{ $table['model']->name }}</td>
                                        <td class="table-count">{{ $table['assigned_count'] }}/{{ $table['seat_count'] }}</td>
                                    </tr>
                                </table>
                                <table class="seat-list">
                                    @for ($seat = 1; $seat <= $table['seat_count']; $seat++)
                                        @php($guest = $table['assignments']->get($seat))
                                        <tr>
                                            <td class="seat-number">#{{ $seat }}</td>
                                            <td>
                                                @if ($guest)
                                                    <strong class="guest-name">{{ $guest['label'] }}</strong>
                                                    <div class="guest-group">{{ $guest['group'] }}</div>
                                                @else
                                                    <span class="empty-seat">Empty</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endfor
                                </table>
                            </article>
                        </td>
                    @endforeach
                    @for ($index = $row->count(); $index < 3; $index++)
                        <td class="tables-grid-cell"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    </section>
</body>
</html>
