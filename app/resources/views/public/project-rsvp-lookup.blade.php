<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} RSVP</title>
    @include('partials.favicons')
    <style>
        body {
            margin: 0;
            font-family: Inter, system-ui, sans-serif;
            color: #2d2a26;
            background:
                linear-gradient(rgba(247, 243, 237, .82), rgba(247, 243, 237, .92)),
                url('{{ asset('images/bg.jpg') }}') center center / cover fixed;
        }
        .page { width: min(1040px, calc(100% - 2rem)); margin: 0 auto; padding: 2rem 0; }
        .hero, .card { background: rgba(255,255,255,.94); border: 1px solid #e8e0d6; border-radius: 14px; box-shadow: 0 20px 44px rgba(45,42,38,.07); }
        .hero { overflow: hidden; margin-bottom: 1rem; }
        .hero-image { min-height: 18rem; background: linear-gradient(180deg, rgba(24,18,14,.14), rgba(24,18,14,.54)), var(--cover-image, linear-gradient(135deg, #d8c4a1, #f3eadc)); background-size: cover; background-position: center; display: flex; align-items: end; }
        .hero-content { width: 100%; padding: 1.35rem; color: #fff; text-shadow: 0 2px 18px rgba(0,0,0,.32); }
        h1 { margin: 0; font-family: Georgia, serif; font-size: clamp(2rem, 5vw, 4rem); line-height: 1.04; }
        .meta { display: flex; flex-wrap: wrap; gap: .55rem .9rem; margin: .65rem 0 0; color: rgba(255,255,255,.9); }
        .meta span:not(:last-child)::after { content: "•"; margin-left: .9rem; opacity: .75; }
        .guest-line { margin: .7rem 0 0; font-size: 1.05rem; color: rgba(255,255,255,.96); }
        .card { padding: 1.4rem; }
        .section { padding-top: 1.1rem; margin-top: 1.1rem; border-top: 1px solid #eadfce; }
        .section:first-child { padding-top: 0; margin-top: 0; border-top: 0; }
        h2 { margin: 0 0 .85rem; font-size: .82rem; letter-spacing: .16em; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        label span { display: block; margin-bottom: .35rem; color: #5e5852; font-size: .75rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .required-mark { display: inline; margin: 0 0 0 .2rem; color: #dc2626; }
        input { width: 100%; min-height: 2.8rem; box-sizing: border-box; border: 1px solid #ddd2c5; border-radius: 8px; background: #fff; padding: .7rem .85rem; color: #2d2a26; font: inherit; }
        .help { margin: .3rem 0 0; color: #8d847b; font-size: .82rem; line-height: 1.45; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 3rem; padding: 0 1.2rem; border: 1px solid #b9975b; border-radius: 8px; background: #b9975b; color: #fff; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; cursor: pointer; }
        .notice { margin-bottom: 1rem; padding: .9rem 1rem; border-radius: 10px; background: #fff4df; color: #7a4f13; }
        .field-error { margin: .35rem 0 0; color: #b42318; font-size: .82rem; }
        @media (max-width: 760px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <main class="page">
        @php
            $coverUrl = $project->cover_image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover_image_path) : null;
            $dateLabel = $project->event_start_date
                ? ($project->event_end_date && ! $project->event_end_date->isSameDay($project->event_start_date)
                    ? $project->event_start_date->format('F j') . ' - ' . $project->event_end_date->format('F j, Y')
                    : $project->event_start_date->format('F j, Y'))
                : 'Date to be defined';
            $locationLabel = collect([$project->locality, $project->region])->filter()->implode(', ');
            $partnerLabel = $project->coupleNames();
        @endphp
        <section class="hero" style="{{ $coverUrl ? '--cover-image: url(' . $coverUrl . ')' : '' }}">
            <div class="hero-image">
                <div class="hero-content">
                    <h1>{{ $project->name }}</h1>
                    <div class="meta">
                        <span>{{ $dateLabel }}</span>
                        @if ($locationLabel)
                            <span>{{ $locationLabel }}</span>
                        @endif
                        @if ($partnerLabel)
                            <span>{{ $partnerLabel }}</span>
                        @endif
                    </div>
                    <p class="guest-line">Find your personal RSVP</p>
                </div>
            </div>
        </section>

        @if (session('lookup_error'))
            <div class="notice">{{ session('lookup_error') }}</div>
        @endif

        <form class="card" method="POST" action="{{ route('public.project-rsvp.lookup.submit', ['projectAlias' => $project->alias]) }}">
            @csrf
            <section class="section">
                <h2>Access your RSVP</h2>
                <p class="help">Enter your last name and either the email or phone number used on the guest list.</p>
                <div class="grid">
                    <label>
                        <span>Last name <span class="required-mark">*</span></span>
                        <input name="last_name" value="{{ old('last_name') }}" required>
                        @error('last_name')<p class="field-error">{{ $message }}</p>@enderror
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </label>
                    <label>
                        <span>Phone</span>
                        <input name="phone" value="{{ old('phone') }}">
                        @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                    </label>
                </div>
            </section>
            <section class="section">
                <button type="submit" class="button">Find RSVP</button>
            </section>
        </form>
    </main>
</body>
</html>
