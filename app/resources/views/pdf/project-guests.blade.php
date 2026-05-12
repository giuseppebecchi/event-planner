<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $project->name }} Guests</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #25211c; font-size: 10px; line-height: 1.45; }
        h1 { margin: 0 0 4px; font-size: 18px; text-transform: uppercase; letter-spacing: .04em; }
        .meta { color: #766c61; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0e8dc; color: #332d25; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: .05em; }
        th, td { border: 1px solid #ded4c8; padding: 5px; vertical-align: top; }
        .group td { background: #f8f1e8; color: #3b342d; font-weight: bold; text-transform: uppercase; }
        .muted { color: #7c7266; }
        .summary-title { margin: 18px 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; }
        .summary-table { width: 58%; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>{{ $project->name }} Guests</h1>
    <div class="meta">
        {{ $project->event_start_date?->format('F j, Y') ?: 'Date to be defined' }}
        @if ($project->locality || $project->region)
            &middot; {{ collect([$project->locality, $project->region])->filter()->implode(', ') }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>RSVP #</th>
                <th>Family / group</th>
                <th>Guest name</th>
                <th>Role</th>
                <th>Confirmed</th>
                <th>Completed at</th>
                <th>Ceremony</th>
                <th>Reception</th>
                <th>Contact</th>
                @foreach ($fields as $field)
                    <th>{{ $field['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $group)
                @php($guest = $group['guest'])
                <tr class="group">
                    <td colspan="{{ 9 + count($fields) }}">
                        RSVP {{ $group['rsvp_number'] ?: '-' }} - {{ $group['label'] }}
                    </td>
                </tr>
                @foreach ($group['rows'] as $row)
                    <tr>
                        <td>{{ $guest->rsvp_number }}</td>
                        <td>{{ $group['label'] }}</td>
                        <td><strong>{{ $row['name'] }}</strong></td>
                        <td>{{ $row['role'] }}</td>
                        <td>{{ $page->formatAttendanceForRow($row) }}</td>
                        <td>{{ $guest->rsvp_completed_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ (int) $guest->ceremony === 1 ? 'Yes' : ((int) $guest->ceremony === -1 ? 'No' : '') }}</td>
                        <td>{{ (int) $guest->reception === 1 ? 'Yes' : ((int) $guest->reception === -1 ? 'No' : '') }}</td>
                        <td>{{ collect([$guest->email, $guest->phone])->filter()->implode(' / ') }}</td>
                        @foreach ($fields as $field)
                            <td>{{ $page->formatFieldResponseForRow($row, $field) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if ($summaries !== [])
        <h2 class="summary-title">Response summary</h2>
        @foreach ($summaries as $summary)
            <table class="summary-table">
                <thead>
                    <tr>
                        <th colspan="2">{{ $summary['label'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['items'] as $item)
                        <tr>
                            <td>{{ $item['label'] }}</td>
                            <td>{{ $item['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endforeach
    @endif
</body>
</html>
