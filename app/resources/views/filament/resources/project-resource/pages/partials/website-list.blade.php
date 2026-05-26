<div class="wm-website-repeat">
    <div class="wm-website-repeat-head">
        <h3 class="wm-website-repeat-title">{{ $title }}</h3>
        <x-filament::button size="sm" color="gray" wire:click="addItem('{{ $section }}', '{{ $list }}')">
            Add
        </x-filament::button>
    </div>

    @forelse (($website[$section][$list] ?? []) as $index => $item)
        <div class="wm-website-item">
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
                    @include('filament.resources.project-resource.pages.partials.website-field', ['path' => "website.$section.$list.$index.location", 'label' => 'Location'])
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
