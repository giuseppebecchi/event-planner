@php
    $settings = $website['settings'] ?? [];
    $home = $website['home'] ?? [];
    $accent = $settings['accent_color'] ?? '#b08c8f';
    $bg = $settings['background_color'] ?? '#fbf7f4';
    $text = $settings['text_color'] ?? '#4d4141';
    $fontPreset = $settings['font_preset'] ?? 'allura';
    $signatureFonts = [
        'allura' => "'Allura', cursive",
        'parisienne' => "'Parisienne', cursive",
        'great_vibes' => "'Great Vibes', cursive",
    ];
    $signatureFont = $signatureFonts[$fontPreset] ?? $signatureFonts['allura'];
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
    $defaultHeroText = $home['eyebrow'] ?: "We're getting married";
    $heroSlides = collect($home['hero_images'] ?? [])
        ->map(fn ($item): array => [
            'url' => $imageUrl($item['url'] ?? null),
            'caption' => trim((string) ($item['caption'] ?? '')),
        ])
        ->when(filled($home['hero_image'] ?? null), fn ($slides) => $slides->push([
            'url' => $imageUrl($home['hero_image']),
            'caption' => '',
        ]))
        ->filter(fn (array $item): bool => filled($item['url']))
        ->unique('url')
        ->values();

    if ($heroSlides->isEmpty()) {
        $heroSlides = collect([['url' => asset('images/bg.jpg'), 'caption' => '']]);
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $home['title'] ?: $project->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Great+Vibes&family=Inter:wght@400;500;600;700&family=Parisienne&display=swap" rel="stylesheet">
    <style>
        :root { --wmw-accent: {{ $accent }}; --wmw-bg: {{ $bg }}; --wmw-text: {{ $text }}; --wmw-script: {!! $signatureFont !!}; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: var(--wmw-bg); color: var(--wmw-text); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .site-header { position: sticky; top: 0; z-index: 20; background: color-mix(in srgb, var(--wmw-bg) 94%, white); border-bottom: 1px solid color-mix(in srgb, var(--wmw-accent) 34%, transparent); }
        .brand { padding: 1.05rem 1rem .45rem; text-align: center; font-family: var(--wmw-script); font-size: clamp(2.15rem, 4vw, 3.8rem); line-height: .92; color: color-mix(in srgb, var(--wmw-accent) 68%, #3d3333); }
        .nav { display: flex; justify-content: center; gap: .35rem 1.1rem; overflow-x: auto; padding: .3rem 1rem .8rem; }
        .nav a { flex: 0 0 auto; color: var(--wmw-text); text-decoration: none; font-size: .78rem; font-weight: 700; text-transform: uppercase; padding-bottom: .35rem; border-bottom: 2px solid transparent; }
        .nav a:hover { border-bottom-color: var(--wmw-accent); }
        .hero { position: relative; min-height: min(66rem, calc(100vh - 7rem)); display: grid; place-items: center; overflow: hidden; color: white; text-align: center; background: #2d2a26; }
        .hero::after { content: ""; position: absolute; inset: 0; z-index: 1; background: linear-gradient(180deg, rgba(0,0,0,.08), rgba(0,0,0,.24)); }
        .hero-slide { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transform: scale(1.04); transition: opacity 900ms ease, transform 4.8s ease; }
        .hero-slide.is-active { opacity: 1; transform: scale(1); }
        .hero-inner { position: relative; z-index: 2; padding: 4rem 1rem; text-shadow: 0 2px 18px rgba(0,0,0,.36); }
        .hero-kicker { display: none; margin: 0 0 1rem; font-family: var(--wmw-script); font-size: clamp(2.6rem, 7vw, 7rem); line-height: .9; color: rgba(255,255,255,.94); }
        .hero-kicker.is-active { display: block; }
        .section { width: min(860px, calc(100% - 2rem)); margin: 0 auto; padding: clamp(3rem, 8vw, 6.5rem) 0; text-align: center; }
        .section.wide { width: min(1080px, calc(100% - 2rem)); }
        h1, h2 { font-weight: 400; color: color-mix(in srgb, var(--wmw-accent) 54%, var(--wmw-text)); }
        h1 { margin: 0; font-family: var(--wmw-script); font-size: clamp(4rem, 10vw, 9rem); line-height: .86; }
        h2 { margin: 0 0 1.4rem; font-family: var(--wmw-script); font-size: clamp(3rem, 6vw, 5.6rem); line-height: .9; }
        p { line-height: 1.72; font-size: 1.06rem; }
        .event-meta { margin-top: 3rem; display: grid; gap: 1rem; }
        .date { font-family: var(--wmw-script); font-size: clamp(2.8rem, 5vw, 5.2rem); line-height: .95; color: color-mix(in srgb, var(--wmw-accent) 58%, var(--wmw-text)); }
        .button { display: inline-flex; align-items: center; justify-content: center; min-width: 12rem; min-height: 3.1rem; padding: 0 1.3rem; border: 0; border-radius: .28rem; background: var(--wmw-accent); color: #161111; font-weight: 700; text-decoration: none; }
        .image { width: min(720px, 100%); margin: 2rem auto; display: block; object-fit: cover; }
        .cards { display: grid; gap: 2.8rem; }
        .card { text-align: center; }
        .card h3 { margin: .4rem 0 .6rem; font-family: var(--wmw-script); font-weight: 400; font-size: clamp(2.3rem, 4vw, 4rem); line-height: .95; color: color-mix(in srgb, var(--wmw-accent) 56%, var(--wmw-text)); }
        .card .meta { margin: 0 0 .75rem; font-style: italic; color: color-mix(in srgb, var(--wmw-text) 70%, white); }
        .gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem; }
        .gallery img { width: 100%; aspect-ratio: 4 / 5; object-fit: cover; display: block; }
        .party { display: grid; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); gap: 1.6rem; }
        .avatar { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; margin-bottom: .9rem; }
        .faq { text-align: left; border-top: 1px solid color-mix(in srgb, var(--wmw-accent) 35%, transparent); padding: 1rem 0; }
        .faq strong { display: block; margin-bottom: .25rem; }
        footer { text-align: center; padding: 3rem 1rem; color: color-mix(in srgb, var(--wmw-text) 75%, white); }
        @media (max-width: 720px) { .brand { font-size: 2.4rem; } .nav { justify-content: flex-start; } .hero { min-height: 62vh; } }
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
        <section id="home" class="hero">
            @foreach ($heroSlides as $index => $hero)
                <img
                    class="hero-slide {{ $index === 0 ? 'is-active' : '' }}"
                    src="{{ $hero['url'] }}"
                    alt=""
                >
            @endforeach
            <div class="hero-inner">
                @foreach ($heroSlides as $index => $hero)
                    <p class="hero-kicker {{ $index === 0 ? 'is-active' : '' }}">{{ $hero['caption'] ?: $defaultHeroText }}</p>
                @endforeach
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
    <script>
        (() => {
            const slides = Array.from(document.querySelectorAll('.hero-slide'));
            const captions = Array.from(document.querySelectorAll('.hero-kicker'));
            if (slides.length < 2) {
                return;
            }

            let active = 0;
            window.setInterval(() => {
                slides[active].classList.remove('is-active');
                captions[active]?.classList.remove('is-active');
                active = (active + 1) % slides.length;
                slides[active].classList.add('is-active');
                captions[active]?.classList.add('is-active');
            }, 5000);
        })();
    </script>
</body>
</html>
