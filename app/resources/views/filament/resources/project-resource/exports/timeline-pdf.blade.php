<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $project->name }} Timeline</title>
    <style>
        @page {
            margin: 118px 42px 54px 42px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #26221d;
            font-size: 11px;
            line-height: 1.55;
        }

        .pdf-header {
            position: fixed;
            top: -88px;
            left: 0;
            right: 0;
            height: 72px;
            border-bottom: 1px solid #d9ccb9;
            padding-bottom: 12px;
        }

        .pdf-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pdf-header-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #1f1a15;
        }

        .pdf-header-subtitle {
            margin-top: 4px;
            font-size: 10px;
            color: #766c61;
        }

        .pdf-header-meta {
            text-align: right;
            vertical-align: top;
            font-size: 10px;
            color: #62584d;
        }

        .pdf-header-meta strong {
            color: #1f1a15;
        }

        .pdf-day {
            margin-bottom: 24px;
            page-break-inside: avoid;
        }

        .pdf-day-head {
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #ebe3d9;
        }

        .pdf-day-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #1f1a15;
        }

        .pdf-day-meta {
            margin-top: 4px;
            font-size: 10px;
            color: #7c7266;
        }

        .pdf-item {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .pdf-time {
            width: 88px;
            vertical-align: top;
            padding: 2px 12px 0 0;
            text-align: right;
            color: #594d40;
            font-size: 11px;
            font-weight: 700;
        }

        .pdf-time-range {
            display: block;
            margin-top: 2px;
            font-size: 9px;
            font-weight: 400;
            color: #887d71;
        }

        .pdf-content {
            vertical-align: top;
            padding-left: 14px;
            border-left: 2px solid #d6b47b;
        }

        .pdf-item-title {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: #1f1a15;
        }

        .pdf-tags {
            margin-top: 5px;
            font-size: 9px;
            color: #7b6e61;
        }

        .pdf-tag {
            display: inline-block;
            margin-right: 8px;
        }

        .pdf-text {
            margin-top: 6px;
            color: #433b33;
        }

        .pdf-label {
            font-weight: 700;
            color: #1f1a15;
        }

        .pdf-images {
            margin-top: 8px;
            font-size: 0;
        }

        .pdf-image {
            display: inline-block;
            width: 82px;
            height: 82px;
            margin: 0 8px 8px 0;
            border-radius: 8px;
            overflow: hidden;
            background: #f0e9df;
            vertical-align: top;
        }

        .pdf-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pdf-empty {
            color: #8b8175;
            font-style: italic;
        }

        .pdf-footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 9px;
            color: #8a8176;
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <table class="pdf-header-table">
            <tr>
                <td>
                    <div class="pdf-header-title">{{ $project->name }}</div>
                    <div class="pdf-header-subtitle">
                        Timeline export
                        @if ($partners)
                            · {{ $partners }}
                        @endif
                    </div>
                </td>
                <td class="pdf-header-meta">
                    @if ($location)
                        <div><strong>Location:</strong> {{ $location }}</div>
                    @endif
                    <div><strong>Date:</strong> {{ $dateRange }}</div>
                    <div><strong>Status:</strong> {{ \App\Models\Project::STATUS_OPTIONS[$project->status] ?? $project->status }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="pdf-footer">Timeline PDF</div>

    @forelse ($days as $day)
        <section class="pdf-day">
            <div class="pdf-day-head">
                <h2 class="pdf-day-title">{{ $day['date']->format('l, F j, Y') }}</h2>
                <div class="pdf-day-meta">
                    {{ count($day['items']) }} items
                    @if ($day['sunset_time'])
                        · Sunset {{ $day['sunset_time']->format('H:i') }}
                    @endif
                </div>
            </div>

            @forelse ($day['items'] as $item)
                <table class="pdf-item">
                    <tr>
                        <td class="pdf-time">
                            {{ $item['start_time'] ?: '—' }}
                            @if ($item['end_time'])
                                <span class="pdf-time-range">to {{ $item['end_time'] }}</span>
                            @endif
                        </td>
                        <td class="pdf-content">
                            <h3 class="pdf-item-title">{{ $item['title'] }}</h3>

                            @if ($item['location'] || $item['supplier_name'] || $item['sunset_time'])
                                <div class="pdf-tags">
                                    @if ($item['location'])
                                        <span class="pdf-tag">{{ $item['location'] }}</span>
                                    @endif
                                    @if ($item['supplier_name'])
                                        <span class="pdf-tag">{{ $item['supplier_name'] }}</span>
                                    @endif
                                    @if ($item['sunset_time'])
                                        <span class="pdf-tag">Sunset {{ $item['sunset_time'] }}</span>
                                    @endif
                                </div>
                            @endif

                            @if ($item['description'])
                                <div class="pdf-text">{{ $item['description'] }}</div>
                            @endif

                            @if ($item['notes'])
                                <div class="pdf-text"><span class="pdf-label">Notes:</span> {{ $item['notes'] }}</div>
                            @endif

                            @if (! empty($item['images']))
                                <div class="pdf-images">
                                    @foreach ($item['images'] as $image)
                                        <span class="pdf-image">
                                            <img src="{{ $image }}" alt="Timeline image">
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            @empty
                <div class="pdf-empty">No timeline items for this day yet.</div>
            @endforelse
        </section>
    @empty
        <div class="pdf-empty">No timeline days available for this project.</div>
    @endforelse
</body>
</html>
