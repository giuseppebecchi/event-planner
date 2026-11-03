<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>{{ $project->name }} - Programma eventi</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; color: #2d2a26; font-size: 11px; line-height: 1.55; }
        h1 { margin: 0; font-size: 25px; }
        h2 { margin: 0 0 8px; font-size: 18px; color: #2e4a62; }
        h3 { margin: 14px 0 7px; font-size: 13px; }
        p { margin: 0 0 8px; }
        .header { padding-bottom: 16px; border-bottom: 2px solid #2e4a62; }
        .meta { margin-top: 6px; color: #746d66; }
        .event { margin-top: 24px; }
        .event-heading { page-break-inside: avoid; }
        .timing { margin: 12px 0; padding: 10px; background: #fbf8f4; border: 1px solid #e4ddd5; }
        .program { padding: 14px 16px; border: 1px solid #e4ddd5; background: #fffdfa; }
        .program ul, .program ol { margin-top: 6px; padding-left: 20px; }
        .program li { margin-bottom: 4px; }
        .generated { margin-top: 20px; color: #91877d; font-size: 9px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $project->name }}</h1>
        <div class="meta">Programma eventi dal {{ $startsAt->format('d/m/Y') }} al {{ $endsAt->format('d/m/Y') }}</div>
        @if ($project->coupleNames())
            <div class="meta">{{ $project->coupleNames() }}</div>
        @endif
    </header>

    @foreach ($events as $event)
        <section class="event">
            <div class="event-heading">
                <h2>{{ $event->title }}</h2>
                <div class="timing">
                    {{ $event->starts_at->format('d/m/Y') }}
                    @if (! $event->starts_at->isSameDay($event->ends_at ?: $event->starts_at))
                        - {{ ($event->ends_at ?: $event->starts_at)->format('d/m/Y') }}
                    @endif
                    · {{ $event->is_all_day ? 'Tutto il giorno' : $project->formatTimeForDisplay($event->starts_at) . ' - ' . $project->formatTimeForDisplay($event->ends_at ?: $event->starts_at) }}
                </div>
            </div>
            @if (filled($event->description))
                <p>{{ $event->description }}</p>
            @endif
            @if (filled($event->program_html))
                <div class="program">{!! $event->program_html !!}</div>
            @endif
        </section>
    @endforeach

    <div class="generated">Generato il {{ $generatedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
