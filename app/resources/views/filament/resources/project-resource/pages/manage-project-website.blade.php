<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $website = $this->website;
        $activeTab = $this->activeTab;
        $tabs = $this->tabs();
        $warnings = $this->sectionWarnings();
        $publicUrl = $this->publicWebsiteUrl();
        $rsvpUrl = $this->firstRsvpUrl();
    @endphp

    <style>
        .wm-website-page { display: flex; flex-direction: column; gap: 1rem; }
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
        .wm-website-repeat { display: grid; gap: .8rem; }
        .wm-website-repeat-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-top: .2rem; }
        .wm-website-repeat-title { margin: 0; color: #2d2a26; font-size: .85rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .wm-website-item { padding: .9rem; border: 1px solid #eee1d5; border-radius: .95rem; background: #fffdf9; display: grid; gap: .8rem; }
        .wm-website-item-head { display: flex; justify-content: space-between; gap: .75rem; align-items: center; color: #6b625b; font-weight: 900; }
        .wm-website-actions { display: flex; justify-content: flex-end; gap: .7rem; padding: 1rem; position: sticky; bottom: 0; background: rgba(251,247,244,.88); backdrop-filter: blur(8px); border-top: 1px solid #eadfd4; }
        @media (max-width: 900px) { .wm-website-top, .wm-website-editor, .wm-website-grid, .wm-website-stats { grid-template-columns: 1fr; } .wm-website-shell { width: min(100%, calc(100% - 1rem)); } }
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
                    <div class="wm-website-field">
                        <label>Accent color</label>
                        <input class="wm-website-input" type="color" wire:model.live="website.settings.accent_color">
                    </div>
                    <div class="wm-website-field">
                        <label>Background color</label>
                        <input class="wm-website-input" type="color" wire:model.live="website.settings.background_color">
                    </div>
                    <div class="wm-website-field">
                        <label>Text color</label>
                        <input class="wm-website-input" type="color" wire:model.live="website.settings.text_color">
                    </div>
                    <div class="wm-website-field">
                        <label>Font preset</label>
                        <select class="wm-website-select" wire:model="website.settings.font_preset">
                            <option value="classic">Classic editorial</option>
                            <option value="modern">Modern serif</option>
                            <option value="minimal">Minimal clean</option>
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
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.location', 'label' => 'Location'])
                            @include('filament.resources.project-resource.pages.partials.website-field', ['path' => 'website.home.hero_image', 'label' => 'Hero image URL', 'full' => true])
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
