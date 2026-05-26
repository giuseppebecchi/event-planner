@php
    $settings = $website['settings'] ?? [];
    $home = $website['home'] ?? [];
    $accent = $settings['accent_color'] ?? '#b08c8f';
    $bg = $settings['background_color'] ?? '#fbf7f4';
    $text = $settings['text_color'] ?? '#4d4141';
    $tabs = [
        'home' => 'Home',
        'schedule' => 'Schedule',
        'travel' => 'Travel',
        'registry' => 'Registry',
        'wedding_party' => 'Wedding Party',
        'gallery' => 'Gallery',
        'things_to_do' => 'Things To Do',
        'faqs' => 'FAQs',
        'events' => 'Welcome Party & Wedding Event',
        'rsvp' => 'RSVP',
    ];
    $imageUrl = function (?string $value): ?string {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($value);
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $home['title'] ?: $project->name }}</title>
    <style>
        :root { --wmw-accent: {{ $accent }}; --wmw-bg: {{ $bg }}; --wmw-text: {{ $text }}; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: var(--wmw-bg); color: var(--wmw-text); font-family: Georgia, "Times New Roman", serif; }
        .site-header { position: sticky; top: 0; z-index: 20; background: color-mix(in srgb, var(--wmw-bg) 94%, white); border-bottom: 1px solid color-mix(in srgb, var(--wmw-accent) 34%, transparent); }
        .brand { padding: 1.05rem 1rem .45rem; text-align: center; font-style: italic; font-size: clamp(1.65rem, 3vw, 2.45rem); letter-spacing: .02em; color: color-mix(in srgb, var(--wmw-accent) 62%, #3d3333); }
        .nav { display: flex; justify-content: center; gap: .35rem 1.1rem; overflow-x: auto; padding: .3rem 1rem .8rem; }
        .nav a { flex: 0 0 auto; color: var(--wmw-text); text-decoration: none; font-size: .94rem; padding-bottom: .35rem; border-bottom: 2px solid transparent; }
        .nav a:hover { border-bottom-color: var(--wmw-accent); }
        .hero { min-height: min(66rem, calc(100vh - 7rem)); display: grid; place-items: center; background: linear-gradient(180deg, rgba(0,0,0,.08), rgba(0,0,0,.2)), var(--hero) center / cover; color: white; text-align: center; }
        .hero-inner { padding: 4rem 1rem; text-shadow: 0 2px 18px rgba(0,0,0,.36); }
        .hero-kicker { margin: 0 0 1rem; font-style: italic; font-size: clamp(1.8rem, 4vw, 4.2rem); color: rgba(255,255,255,.92); }
        .section { width: min(860px, calc(100% - 2rem)); margin: 0 auto; padding: clamp(3rem, 8vw, 6.5rem) 0; text-align: center; }
        .section.wide { width: min(1080px, calc(100% - 2rem)); }
        h1, h2 { font-weight: 400; color: color-mix(in srgb, var(--wmw-accent) 54%, var(--wmw-text)); }
        h1 { margin: 0; font-style: italic; font-size: clamp(3rem, 8vw, 7rem); line-height: .95; }
        h2 { margin: 0 0 1.4rem; font-size: clamp(2rem, 4vw, 3.5rem); font-style: italic; }
        p { line-height: 1.72; font-size: 1.06rem; }
        .event-meta { margin-top: 3rem; display: grid; gap: 1rem; }
        .date { font-style: italic; font-size: clamp(2rem, 4vw, 4rem); color: color-mix(in srgb, var(--wmw-accent) 58%, var(--wmw-text)); }
        .button { display: inline-flex; align-items: center; justify-content: center; min-width: 12rem; min-height: 3.1rem; padding: 0 1.3rem; border: 0; border-radius: .28rem; background: var(--wmw-accent); color: #161111; font-weight: 700; text-decoration: none; }
        .image { width: min(720px, 100%); margin: 2rem auto; display: block; object-fit: cover; }
        .cards { display: grid; gap: 2.8rem; }
        .card { text-align: center; }
        .card h3 { margin: .4rem 0 .6rem; font-weight: 400; font-style: italic; font-size: clamp(1.7rem, 3vw, 2.8rem); color: color-mix(in srgb, var(--wmw-accent) 56%, var(--wmw-text)); }
        .card .meta { margin: 0 0 .75rem; font-style: italic; color: color-mix(in srgb, var(--wmw-text) 70%, white); }
        .gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem; }
        .gallery img { width: 100%; aspect-ratio: 4 / 5; object-fit: cover; display: block; }
        .party { display: grid; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); gap: 1.6rem; }
        .avatar { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; margin-bottom: .9rem; }
        .faq { text-align: left; border-top: 1px solid color-mix(in srgb, var(--wmw-accent) 35%, transparent); padding: 1rem 0; }
        .faq strong { display: block; margin-bottom: .25rem; }
        footer { text-align: center; padding: 3rem 1rem; color: color-mix(in srgb, var(--wmw-text) 75%, white); }
        @media (max-width: 720px) { .brand { font-size: 1.5rem; } .nav { justify-content: flex-start; } .hero { min-height: 62vh; } }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="brand">{{ $settings['signature'] ?: ($home['title'] ?: $project->name) }}</div>
        <nav class="nav">
            @foreach ($tabs as $key => $label)
                @if (($website[$key]['enabled'] ?? true) && ($key !== 'home'))
                    <a href="#{{ $key }}">{{ $label }}</a>
                @elseif ($key === 'home')
                    <a href="#home">Home</a>
                @endif
            @endforeach
        </nav>
    </header>

    <main>
        @php $hero = $imageUrl($home['hero_image'] ?? null) ?: asset('images/bg.jpg'); @endphp
        <section id="home" class="hero" style="--hero: url('{{ $hero }}')">
            <div class="hero-inner">
                <p class="hero-kicker">{{ $home['eyebrow'] ?: "We're getting married" }}</p>
            </div>
        </section>

        <section class="section">
            <h1>{{ $home['title'] ?: $project->name }}</h1>
            @if ($home['subtitle'] ?? null)
                <p>{{ $home['subtitle'] }}</p>
            @endif
            <div class="event-meta">
                @if ($home['date'] ?? null)<div class="date">{{ $home['date'] }}</div>@endif
                @if ($home['location'] ?? null)<p>{{ $home['location'] }}</p>@endif
                <p><a class="button" href="#rsvp">RSVP</a></p>
            </div>
            @if ($imageUrl($home['intro_image'] ?? null))
                <img class="image" src="{{ $imageUrl($home['intro_image']) }}" alt="">
            @endif
            @if (($home['intro_title'] ?? null) || ($home['intro_text'] ?? null))
                <h2>{{ $home['intro_title'] }}</h2>
                <p>{{ $home['intro_text'] }}</p>
            @endif
        </section>

        @if ($website['schedule']['enabled'] ?? true)
            <section id="schedule" class="section">
                <h2>{{ $website['schedule']['title'] }}</h2>
                <p>{{ $website['schedule']['intro'] }}</p>
                <div class="cards">
                    @foreach (($website['schedule']['items'] ?? []) as $item)
                        <article class="card">
                            <p class="meta">{{ collect([$item['date'] ?? null, $item['time'] ?? null])->filter()->implode(' - ') }}</p>
                            <h3>{{ $item['title'] ?? '' }}</h3>
                            <p class="meta">{{ collect([$item['location'] ?? null, $item['address'] ?? null])->filter()->implode(', ') }}</p>
                            <p>{{ $item['text'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($website['travel']['enabled'] ?? true)
            <section id="travel" class="section">
                <h2>{{ $website['travel']['title'] }}</h2>
                @if ($imageUrl($website['travel']['image'] ?? null))<img class="image" src="{{ $imageUrl($website['travel']['image']) }}" alt="">@endif
                <p>{{ $website['travel']['intro'] }}</p>
                <div class="cards">
                    @foreach (array_merge($website['travel']['hotels'] ?? [], $website['travel']['transportation'] ?? []) as $item)
                        <article class="card">
                            <p class="meta">{{ $item['type'] ?? '' }}</p>
                            <h3>{{ $item['name'] ?? $item['title'] ?? '' }}</h3>
                            <p class="meta">{{ $item['address'] ?? '' }}</p>
                            <p>{{ $item['description'] ?? '' }}</p>
                            @if ($item['discount'] ?? null)<p>{{ $item['discount'] }}</p>@endif
                            @if ($item['url'] ?? null)<a class="button" href="{{ $item['url'] }}" target="_blank" rel="noopener">View</a>@endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($website['registry']['enabled'] ?? true)
            <section id="registry" class="section">
                <h2>{{ $website['registry']['title'] }}</h2>
                <p>{{ $website['registry']['intro'] }}</p>
                @if ($website['registry']['url'] ?? null)<a class="button" href="{{ $website['registry']['url'] }}" target="_blank" rel="noopener">{{ $website['registry']['button_label'] ?: 'View registry' }}</a>@endif
            </section>
        @endif

        @if ($website['wedding_party']['enabled'] ?? true)
            <section id="wedding_party" class="section wide">
                <h2>{{ $website['wedding_party']['title'] }}</h2>
                <p>{{ $website['wedding_party']['intro'] }}</p>
                <div class="party">
                    @foreach (($website['wedding_party']['people'] ?? []) as $person)
                        <article>
                            @if ($imageUrl($person['image'] ?? null))<img class="avatar" src="{{ $imageUrl($person['image']) }}" alt="">@endif
                            <h3>{{ $person['name'] ?? '' }}</h3>
                            <p class="meta">{{ $person['role'] ?? '' }}</p>
                            <p>{{ $person['bio'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($website['gallery']['enabled'] ?? true)
            <section id="gallery" class="section wide">
                <h2>{{ $website['gallery']['title'] }}</h2>
                <p>{{ $website['gallery']['intro'] }}</p>
                <div class="gallery">
                    @foreach (($website['gallery']['images'] ?? []) as $image)
                        @if ($imageUrl($image['url'] ?? null))
                            <img src="{{ $imageUrl($image['url']) }}" alt="{{ $image['caption'] ?? '' }}">
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @foreach (['things_to_do', 'events'] as $sectionKey)
            @if ($website[$sectionKey]['enabled'] ?? true)
                <section id="{{ $sectionKey }}" class="section">
                    <h2>{{ $website[$sectionKey]['title'] }}</h2>
                    <p>{{ $website[$sectionKey]['intro'] ?? '' }}</p>
                    <div class="cards">
                        @foreach (($website[$sectionKey]['items'] ?? []) as $item)
                            <article class="card">
                                <p class="meta">{{ collect([$item['date'] ?? null, $item['time'] ?? null])->filter()->implode(' - ') }}</p>
                                <h3>{{ $item['title'] ?? '' }}</h3>
                                <p class="meta">{{ collect([$item['location'] ?? null, $item['address'] ?? null])->filter()->implode(', ') }}</p>
                                <p>{{ $item['text'] ?? '' }}</p>
                                @if ($item['url'] ?? null)<a class="button" href="{{ $item['url'] }}" target="_blank" rel="noopener">View</a>@endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        @if ($website['faqs']['enabled'] ?? true)
            <section id="faqs" class="section">
                <h2>{{ $website['faqs']['title'] }}</h2>
                @foreach (($website['faqs']['items'] ?? []) as $item)
                    <div class="faq">
                        <strong>{{ $item['question'] ?? '' }}</strong>
                        <p>{{ $item['answer'] ?? '' }}</p>
                    </div>
                @endforeach
            </section>
        @endif

        @if ($website['rsvp']['enabled'] ?? true)
            <section id="rsvp" class="section">
                <h2>{{ $website['rsvp']['title'] }}</h2>
                <p>{{ $website['rsvp']['intro'] }}</p>
                @if ($guest)
                    <a class="button" href="{{ $guest->publicRsvpUrl() }}">Open RSVP form</a>
                @endif
            </section>
        @endif
    </main>

    <footer>{{ $settings['footer_text'] ?? '' }}</footer>
</body>
</html>
