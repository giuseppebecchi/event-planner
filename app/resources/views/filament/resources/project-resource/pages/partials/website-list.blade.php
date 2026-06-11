<div class="wm-website-repeat">
    <div class="wm-website-repeat-head">
        <h3 class="wm-website-repeat-title">{{ $title }}</h3>
        <x-filament::button size="sm" color="gray" wire:click="addItem('{{ $section }}', '{{ $list }}')">
            Add
        </x-filament::button>
    </div>

    @forelse (($website[$section][$list] ?? []) as $index => $item)
        <div class="wm-website-item" wire:key="website-{{ $section }}-{{ $list }}-{{ $index }}-{{ md5((string) ($item['url'] ?? $item['name'] ?? $item['title'] ?? $item['question'] ?? $index)) }}">
            <div class="wm-website-item-head">
                <span>{{ $title }} #{{ $index + 1 }}</span>
                <x-filament::button size="sm" color="danger" wire:click="removeItem('{{ $section }}', '{{ $list }}', {{ $index }})">
                    Remove
                </x-filament::button>
            </div>

            <div class="wm-website-grid">
                @if ($section === 'faqs')
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.question", 'label' => 'Question'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.answer", 'label' => 'Answer', 'textarea' => true, 'full' => true])
                @elseif ($list === 'hero_images')
                    @php
                        $heroPreviewUrl = trim((string) ($item['url'] ?? ''));

                        if ($heroPreviewUrl !== '' && ! str_starts_with($heroPreviewUrl, 'http://') && ! str_starts_with($heroPreviewUrl, 'https://') && ! str_starts_with($heroPreviewUrl, '/')) {
                            $heroPreviewUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($heroPreviewUrl);
                        }
                    @endphp
                    <div class="wm-website-field">
                        <label>Image</label>
                        @if ($heroPreviewUrl !== '')
                            <img class="wm-website-thumb" src="{{ $heroPreviewUrl }}" alt="{{ $item['caption'] ?? '' }}">
                        @else
                            <div class="wm-website-thumb is-empty">No image</div>
                        @endif
                    </div>
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.caption", 'label' => 'Text over image'])
                @elseif ($list === 'images')
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.url", 'label' => 'Image URL'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.caption", 'label' => 'Caption'])
                @elseif ($list === 'people')
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.name", 'label' => 'Name'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.role", 'label' => 'Role'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.image", 'label' => 'Image URL'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.bio", 'label' => 'Bio', 'textarea' => true, 'full' => true])
                @elseif ($list === 'hotels')
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.name", 'label' => 'Name'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.type", 'label' => 'Type'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.address", 'label' => 'Address'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.url", 'label' => 'URL'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.discount", 'label' => 'Discount / notes', 'textarea' => true, 'full' => true])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.description", 'label' => 'Description', 'textarea' => true, 'full' => true])
                @elseif ($list === 'transportation')
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.title", 'label' => 'Title'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.type", 'label' => 'Type'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.url", 'label' => 'URL'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.description", 'label' => 'Description', 'textarea' => true, 'full' => true])
                @else
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.title", 'label' => 'Title'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.date", 'label' => 'Date'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.time", 'label' => 'Time'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.location", 'label' => 'Venue'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.address", 'label' => 'Address'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.url", 'label' => 'URL'])
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.text", 'label' => 'Text', 'textarea' => true, 'full' => true])
                @endif
            </div>
        </div>
    @empty
        <p class="wm-website-copy">No items yet.</p>
    @endforelse
</div>
