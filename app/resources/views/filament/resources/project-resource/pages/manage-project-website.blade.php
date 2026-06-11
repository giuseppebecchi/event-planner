<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $website = $this->website;
        $activeTab = $this->activeTab;
        $tabs = $this->tabs();
        $warnings = $this->sectionWarnings();
        $publicUrl = $this->publicWebsiteUrl();
        $rsvpUrl = $this->firstRsvpUrl();
        $palettes = $this->colorPalettes();
        $fontPresets = $this->fontPresets();
    @endphp

    <style>
        .wm-website-page { display: flex; flex-direction: column; gap: 1rem; }
        .wm-event-card { border: 1px solid var(--cup-border-soft, #e8e3dc); border-radius: 1.35rem; background: rgba(255,255,255,.92); box-shadow: 0 20px 42px rgba(45,42,38,.06); }
        .wm-event-top { display: flex; flex-direction: column; gap: .85rem; align-items: start; padding: .9rem 1rem 1rem; }
        .wm-event-top-head { width: 100%; display: grid; grid-template-columns: minmax(0,1fr) auto; gap: .9rem 1rem; align-items: center; }
        .wm-event-top-title { margin: 0; font-family: 'Cinzel', serif; font-size: clamp(1.2rem,1.8vw,1.65rem); line-height: 1.08; color: #2d2a26; }
        .wm-event-top-meta { display: flex; flex-wrap: wrap; gap: .6rem .95rem; margin-top: .4rem; color: #746d66; font-size: .86rem; line-height: 1.5; }
        .wm-event-top-meta span { position: relative; }
        .wm-event-top-meta span:not(:last-child)::after { content: "•"; margin-left: .95rem; color: #c9a96a; }
        .wm-event-top-side { display: flex; align-items: center; gap: .55rem; }
        .wm-event-summary-chip { display: inline-flex; align-items: center; justify-content: center; min-width: 6rem; padding: .62rem .78rem; border-radius: 1rem; border: 1px solid rgba(201,169,106,.22); background: rgba(255,255,255,.85); color: #5f5953; }
        .wm-event-summary-chip-label { margin: 0; font-size: .62rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #9a8f82; }
        .wm-event-summary-chip-value { margin: .16rem 0 0; font-size: .98rem; font-weight: 700; color: #2d2a26; }
        .wm-event-countdown { min-width: 11.5rem; padding: .62rem .82rem; border-radius: 1rem; background: linear-gradient(160deg, rgba(46,74,98,.96), rgba(36,60,81,.98)); color: #f7f3ed; }
        .wm-event-countdown-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .wm-event-countdown-label { margin: 0; font-size: .66rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.64); }
        .wm-event-countdown-edit { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border: 0; border-radius: 999px; background: rgba(255,255,255,.10); color: rgba(255,255,255,.86); cursor: pointer; }
        .wm-event-countdown-edit svg { width: 1rem; height: 1rem; }
        .wm-event-countdown-value { margin: .18rem 0 0; color: #fff; font-size: 1.08rem; font-weight: 700; }
        .wm-event-countdown-meta { margin: .1rem 0 0; color: rgba(255,255,255,.72); font-size: .8rem; }
        .wm-event-top-date-tools { width: 100%; }
        .wm-event-date-editor { display: grid; gap: .85rem; width: 100%; max-width: 38rem; padding: 1rem; border-radius: 1rem; background: #fbf8f4; border: 1px solid #ece5dd; }
        .wm-event-date-toggle { display: inline-flex; align-items: center; gap: .6rem; color: #4d473f; font-weight: 600; }
        .wm-event-date-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: .75rem; }
        .wm-event-date-grid.is-single { grid-template-columns: minmax(0,1fr); max-width: 16rem; }
        .wm-event-date-label { display: block; margin-bottom: .35rem; color: #5e5852; font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .wm-event-date-input { width: 100%; min-height: 2.9rem; border-radius: .95rem; border: 1px solid #ddd2c5; background: #fff; padding: 0 .95rem; color: #2d2a26; }
        .wm-event-date-actions { display: flex; gap: .6rem; }
        .wm-event-workspace { display: flex; align-items: center; gap: .4rem; overflow-x: auto; width: 100%; padding: .28rem; border-radius: 1.2rem; background: rgba(247,243,237,.96); scrollbar-width: none; border: 1px solid #ece5dd; }
        .wm-event-workspace::-webkit-scrollbar { display: none; }
        .wm-event-workspace-link { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; min-height: 2.45rem; padding: 0 .88rem; border-radius: 999px; color: #746d66; font-size: .69rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; white-space: nowrap; text-decoration: none; transition: background-color 120ms ease, color 120ms ease; }
        .wm-event-workspace-link:hover { background: rgba(122,143,123,.10); color: #617563; }
        .wm-event-workspace-link.is-active { background: rgba(122,143,123,.14); color: #2d7a39; }
        .wm-website-shell { width: min(1180px, calc(100% - 2rem)); margin: 0 auto; display: grid; gap: 1rem; }
        .wm-website-card { border: 1px solid #e8e3dc; border-radius: 1.1rem; background: rgba(255,255,255,.94); box-shadow: 0 18px 40px rgba(45,42,38,.06); }
        .wm-website-top { display: grid; grid-template-columns: minmax(0,1fr) minmax(18rem, .4fr); gap: 1rem; }
        .wm-website-panel { padding: 1rem; display: grid; gap: .9rem; }
        .wm-website-title { margin: 0; color: #2d2a26; font-size: 1.05rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .wm-website-copy { margin: 0; color: #6f665f; line-height: 1.55; }
        .wm-website-url { display: grid; grid-template-columns: minmax(0,1fr) auto; gap: .6rem; align-items: center; }
        .wm-website-url code { min-height: 2.65rem; display: flex; align-items: center; overflow: auto; padding: 0 .8rem; border: 1px solid #ded4ca; border-radius: .75rem; background: #fbf7f1; color: #4b4540; white-space: nowrap; }
        .wm-website-stats { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: .75rem; }
        .wm-website-stat { padding: .8rem; border: 1px solid #eee5dc; border-radius: .85rem; background: #fffaf5; }
        .wm-website-stat span { display: block; color: #8b8178; font-size: .65rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        .wm-website-stat strong { display: block; margin-top: .2rem; color: #2d2a26; font-size: 1.35rem; }
        .wm-website-tabs { display: flex; gap: .45rem; overflow-x: auto; padding: .35rem; border: 1px solid #eadfd4; border-radius: 1.1rem; background: #fbf7f1; }
        .wm-website-tab { flex: 0 0 auto; min-height: 2.55rem; border: 0; border-radius: 999px; padding: 0 .9rem; background: transparent; color: #756b63; font-size: .7rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; cursor: pointer; }
        .wm-website-tab.is-active { background: #fff; color: #2d7a39; box-shadow: 0 8px 18px rgba(45,42,38,.08); }
        .wm-website-tab.has-warning { color: #a05a2c; }
        .wm-website-editor { display: grid; grid-template-columns: 15rem minmax(0,1fr); gap: 1rem; }
        .wm-website-warning-list { padding: 1rem; align-self: start; }
        .wm-website-warning-list h3 { margin: 0 0 .7rem; color: #2d2a26; font-size: .8rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        .wm-website-warning { margin: .45rem 0 0; color: #9a4f29; font-size: .82rem; line-height: 1.45; }
        .wm-website-form { padding: 1rem; display: grid; gap: 1rem; }
        .wm-website-section-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-bottom: .75rem; border-bottom: 1px solid #eee5dc; }
        .wm-website-toggle { display: inline-flex; align-items: center; gap: .55rem; color: #5d554f; font-weight: 800; }
        .wm-website-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: .85rem; }
        .wm-website-field.is-full { grid-column: 1 / -1; }
        .wm-website-field label { display: block; margin-bottom: .35rem; color: #5e5852; font-size: .72rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .wm-website-input, .wm-website-textarea, .wm-website-select { width: 100%; min-height: 2.8rem; border-radius: .8rem; border: 1px solid #ddd2c5; background: #fff; padding: .72rem .85rem; color: #2d2a26; }
        .wm-website-textarea { min-height: 6.5rem; resize: vertical; }
        .wm-palette-grid { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); gap: .75rem; }
        .wm-palette-option { display: grid; gap: .65rem; padding: .8rem; border: 1px solid #e6ddd4; border-radius: .85rem; background: #fffdf9; color: #2d2a26; text-align: left; cursor: pointer; }
        .wm-palette-option.is-active { border-color: #2d7a39; box-shadow: 0 0 0 2px rgba(45,122,57,.12); }
        .wm-palette-name { font-size: .72rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .wm-palette-swatches { display: grid; grid-template-columns: repeat(5, 1fr); gap: .28rem; }
        .wm-palette-swatch { height: 1.4rem; border-radius: 999px; border: 1px solid rgba(45,42,38,.12); }
        .wm-upload-row { display: flex; flex-wrap: wrap; align-items: center; gap: .7rem; }
        .wm-upload-input { min-height: 2.8rem; max-width: 100%; border: 1px dashed #d4c7ba; border-radius: .8rem; background: #fff; padding: .55rem .7rem; color: #4b4540; }
        .wm-website-thumb { width: 100%; max-width: 12rem; aspect-ratio: 16 / 10; display: grid; place-items: center; border: 1px solid #e5d8cb; border-radius: .75rem; background: #f7efe6; color: #8b8178; object-fit: cover; font-size: .78rem; font-weight: 800; }
        .wm-website-repeat { display: grid; gap: .8rem; }
        .wm-website-repeat-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-top: .2rem; }
        .wm-website-repeat-title { margin: 0; color: #2d2a26; font-size: .85rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .wm-website-item { padding: .9rem; border: 1px solid #eee1d5; border-radius: .95rem; background: #fffdf9; display: grid; gap: .8rem; }
        .wm-website-item-head { display: flex; justify-content: space-between; gap: .75rem; align-items: center; color: #6b625b; font-weight: 900; }
        .wm-website-actions { display: flex; justify-content: flex-end; gap: .7rem; padding: 1rem; position: sticky; bottom: 0; background: rgba(251,247,244,.88); backdrop-filter: blur(8px); border-top: 1px solid #eadfd4; }
        @media (max-width: 900px) { .wm-event-top-head, .wm-event-date-grid, .wm-website-top, .wm-website-editor, .wm-website-grid, .wm-website-stats { grid-template-columns: 1fr; } .wm-event-top-side { width: 100%; flex-wrap: wrap; } .wm-website-shell { width: min(100%, calc(100% - 1rem)); } }
    </style>

    <div class="wm-website-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'website',
        ])

        <div class="wm-website-shell">
            <section class="wm-website-top">
                <article class="wm-website-card wm-website-panel">
                    <h2 class="wm-website-title">Public website</h2>
                    <p class="wm-website-copy">Manage the guest-facing event website from one JSON configuration. RSVP stays connected to the existing guest RSVP system.</p>
                    <div class="wm-website-grid">
                        <div class="wm-website-field">
                            <label>Event name</label>
                            <input class="wm-website-input" wire:model.live.debounce.500ms="eventName">
                        </div>
                        <div class="wm-website-field">
                            <label>Website alias</label>
                            <input class="wm-website-input" wire:model.live.debounce.500ms="alias">
                        </div>
                    </div>
                    <div class="wm-website-url">
                        <code>{{ $publicUrl }}</code>
                        <x-filament::button tag="a" href="{{ $publicUrl }}" target="_blank">
                            Open site
                        </x-filament::button>
                    </div>
                    @if ($rsvpUrl)
                        <div class="wm-website-url">
                            <code>{{ $rsvpUrl }}</code>
                            <x-filament::button color="gray" tag="a" href="{{ $rsvpUrl }}" target="_blank">
                                Open RSVP
                            </x-filament::button>
                        </div>
                    @endif
                </article>

                <article class="wm-website-card wm-website-panel">
                    <h2 class="wm-website-title">Readiness</h2>
                    <div class="wm-website-stats">
                        <div class="wm-website-stat"><span>Sections</span><strong>{{ count($tabs) }}</strong></div>
                        <div class="wm-website-stat"><span>Warnings</span><strong>{{ $this->totalWarnings() }}</strong></div>
                        <div class="wm-website-stat"><span>Published</span><strong>{{ ($website['settings']['published'] ?? true) ? 'Yes' : 'No' }}</strong></div>
                    </div>
                </article>
            </section>

            <section class="wm-website-card wm-website-panel">
                <div class="wm-website-grid">
                    <div class="wm-website-field is-full">
                        <label>Color palette</label>
                        <div class="wm-palette-grid">
                            @foreach ($palettes as $key => $palette)
                                <button type="button" wire:click="setPalette('{{ $key }}')" class="wm-palette-option {{ ($website['settings']['palette_preset'] ?? null) === $key ? 'is-active' : '' }}">
                                    <span class="wm-palette-name">{{ $palette['name'] }}</span>
                                    <span class="wm-palette-swatches">
                                        @foreach ($palette['swatches'] as $swatch)
                                            <span class="wm-palette-swatch" style="background: {{ $swatch }}"></span>
                                        @endforeach
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="wm-website-field">
                        <label>Font preset</label>
                        <select class="wm-website-select" wire:model="website.settings.font_preset">
                            @foreach ($fontPresets as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wm-website-field">
                        <label>Signature</label>
                        <input class="wm-website-input" wire:model="website.settings.signature">
                    </div>
                    <div class="wm-website-field">
                        <label>Footer text</label>
                        <input class="wm-website-input" wire:model="website.settings.footer_text">
                    </div>
                    <label class="wm-website-toggle">
                        <input type="checkbox" wire:model="website.settings.published">
                        <span>Website published</span>
                    </label>
                </div>
            </section>

            <nav class="wm-website-tabs" aria-label="Website sections">
                @foreach ($tabs as $key => $label)
                    <button type="button" wire:click="$set('activeTab', '{{ $key }}')" class="wm-website-tab {{ $activeTab === $key ? 'is-active' : '' }} {{ count($warnings[$key] ?? []) ? 'has-warning' : '' }}">
                        {{ $label }}{{ count($warnings[$key] ?? []) ? ' !' : '' }}
                    </button>
                @endforeach
            </nav>

            <section class="wm-website-editor">
                <aside class="wm-website-card wm-website-warning-list">
                    <h3>Section warnings</h3>
                    @forelse (($warnings[$activeTab] ?? []) as $warning)
                        <p class="wm-website-warning">{{ $warning }}</p>
                    @empty
                        <p class="wm-website-copy">Minimum content is ready.</p>
                    @endforelse
                </aside>

                <article class="wm-website-card wm-website-form">
                    <div class="wm-website-section-head">
                        <h2 class="wm-website-title">{{ $tabs[$activeTab] }}</h2>
                        <label class="wm-website-toggle">
                            <input type="checkbox" wire:model="website.{{ $activeTab }}.enabled">
                            <span>Show section</span>
                        </label>
                    </div>

                    @if ($activeTab === 'home')
                        <div class="wm-website-grid">
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.title', 'label' => 'Names / title'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.eyebrow', 'label' => 'Hero phrase'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.date', 'label' => 'Event date'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.location', 'label' => 'Venue'])
                            <div class="wm-website-field is-full">
                                <label>Hero slider upload</label>
                                <div class="wm-upload-row">
                                    <input class="wm-upload-input" type="file" wire:model="heroImageUploads" multiple accept="image/*">
                                    <x-filament::button type="button" color="gray" wire:click="uploadHeroImages" wire:loading.attr="disabled" wire:target="heroImageUploads,uploadHeroImages">
                                        Add images
                                    </x-filament::button>
                                </div>
                                @error('heroImageUploads.*') <p class="wm-website-warning">{{ $message }}</p> @enderror
                            </div>
                            @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'home', 'list' => 'hero_images', 'title' => 'Hero slider images'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.subtitle', 'label' => 'Intro text', 'textarea' => true, 'full' => true])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.intro_title', 'label' => 'Story title'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.intro_image', 'label' => 'Story image URL'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.intro_text', 'label' => 'Story text', 'textarea' => true, 'full' => true])
                        </div>
                    @elseif ($activeTab === 'registry')
                        <div class="wm-website-grid">
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.registry.title', 'label' => 'Title'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.registry.button_label', 'label' => 'Button label'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.registry.url', 'label' => 'Registry URL', 'full' => true])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.registry.intro', 'label' => 'Text', 'textarea' => true, 'full' => true])
                        </div>
                    @elseif ($activeTab === 'rsvp')
                        <div class="wm-website-grid">
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.rsvp.title', 'label' => 'Title'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.rsvp.intro', 'label' => 'Text', 'textarea' => true, 'full' => true])
                        </div>
                        <x-filament::button tag="a" color="gray" href="{{ \App\Filament\Resources\ProjectResource::getUrl('guests-rsvp-configuration', ['record' => $record]) }}">
                            Configure RSVP questions
                        </x-filament::button>
                    @elseif ($activeTab === 'travel')
                        <div class="wm-website-grid">
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.travel.title', 'label' => 'Title'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.travel.image', 'label' => 'Image URL'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.travel.intro', 'label' => 'Intro', 'textarea' => true, 'full' => true])
                        </div>
                        @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'travel', 'list' => 'hotels', 'title' => 'Hotels'])
                        @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'travel', 'list' => 'transportation', 'title' => 'Transportation'])
                    @else
                        <div class="wm-website-grid">
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.' . $activeTab . '.title', 'label' => 'Title'])
                            @if ($activeTab !== 'faqs')
                                @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.' . $activeTab . '.intro', 'label' => 'Intro', 'textarea' => true, 'full' => true])
                            @endif
                        </div>

                        @if ($activeTab === 'schedule')
                            @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'schedule', 'list' => 'items', 'title' => 'Moments'])
                        @elseif ($activeTab === 'wedding_party')
                            @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'wedding_party', 'list' => 'people', 'title' => 'People'])
                        @elseif ($activeTab === 'gallery')
                            @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'gallery', 'list' => 'images', 'title' => 'Images'])
                        @elseif ($activeTab === 'things_to_do')
                            @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'things_to_do', 'list' => 'items', 'title' => 'Recommendations'])
                        @elseif ($activeTab === 'faqs')
                            @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'faqs', 'list' => 'items', 'title' => 'Questions'])
                        @elseif ($activeTab === 'events')
                            @include('filament.resources.project-resource.pages.partials.website-list', ['section' => 'events', 'list' => 'items', 'title' => 'Events'])
                        @endif
                    @endif
                </article>
            </section>

            <div class="wm-website-actions">
                <x-filament::button wire:click="saveWebsite">
                    Save website
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
