<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $project->name }} - {{ $event->title }} program</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #2d2a26; font-size: 11px; line-height: 1.55; }
        h1 { margin: 0; font-size: 25px; }
        h2 { margin: 0 0 10px; font-size: 16px; color: #2e4a62; }
        h3 { margin: 16px 0 7px; font-size: 13px; color: #3f3933; }
        p { margin: 0 0 8px; }
        .project-header { padding-bottom: 16px; border-bottom: 2px solid #2e4a62; }
        .meta { margin-top: 6px; color: #746d66; }
        .summary { margin: 20px 0; width: 100%; border-collapse: collapse; }
        .summary td { width: 33.33%; padding: 10px; border: 1px solid #e4ddd5; background: #fbf8f4; vertical-align: top; }
        .label { color: #746d66; font-size: 9px; text-transform: uppercase; letter-spacing: .08em; }
        .value { margin-top: 4px; font-size: 13px; font-weight: bold; }
        .section { margin-top: 18px; }
        .program { padding: 14px 16px; border: 1px solid #e4ddd5; background: #fffdfa; }
        .program ul, .program ol { margin-top: 6px; padding-left: 20px; }
        .program li { margin-bottom: 4px; }
        .generated { margin-top: 20px; color: #91877d; font-size: 9px; }
    </style>
</head>
<body>
    <header class="project-header">
        <h1>{{ $project->name }}</h1>
        <div class="meta">
            @if ($project->coupleNames())
                {{ $project->coupleNames() }}
            @endif
            @if ($project->event_start_date)
                {{ $project->coupleNames() ? ' · ' : '' }}Wedding date {{ $project->event_start_date->format('F j, Y') }}
                @if ($project->event_end_date && ! $project->event_start_date->isSameDay($project->event_end_date))
                    - {{ $project->event_end_date->format('F j, Y') }}
                @endif
            @endif
            @if ($project->displayLocationLabel())
                {{ ($project->coupleNames() || $project->event_start_date) ? ' · ' : '' }}{{ $project->displayLocationLabel() }}
            @endif
        </div>
    </header>

    <section class="section">
        <h2>{{ $event->title }}</h2>
        @if ($event->description)
            <p>{{ $event->description }}</p>
        @endif
    </section>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Start</div>
                <div class="value">
                    {{ $event->starts_at->format('F j, Y') }}
                    @if (! $event->is_all_day)
                        <br>{{ $project->formatTimeForDisplay($event->starts_at) }}
                    @endif
                </div>
            </td>
            <td>
                <div class="label">End</div>
                <div class="value">
                    {{ ($event->ends_at ?: $event->starts_at)->format('F j, Y') }}
                    @if (! $event->is_all_day)
                        <br>{{ $project->formatTimeForDisplay($event->ends_at ?: $event->starts_at) }}
                    @endif
                </div>
            </td>
            <td>
                <div class="label">Timing</div>
                <div class="value">{{ $event->is_all_day ? 'All day' : 'Timed event' }}</div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2>Program</h2>
        <div class="program">{!! $event->program_html !!}</div>
    </section>

    <div class="generated">Generated {{ $generatedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
