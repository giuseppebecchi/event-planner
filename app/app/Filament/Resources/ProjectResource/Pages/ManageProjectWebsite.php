<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Project;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\WithFileUploads;

class ManageProjectWebsite extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.manage-project-website';

    protected static ?string $breadcrumb = 'Website';

    protected Width|string|null $maxContentWidth = Width::Full;

    public array $website = [];

    public string $eventName = '';

    public string $alias = '';

    public string $activeTab = 'home';

    public array $heroImageUploads = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->ensureRecordAlias();

        $this->eventName = (string) $this->getRecord()->name;
        $this->alias = (string) $this->getRecord()->alias;
        $this->website = $this->normalizeWebsite($this->getRecord()->websiteConfiguration());
    }

    public function getTitle(): string|Htmlable
    {
        return (string) $this->getRecordTitle();
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function tabs(): array
    {
        return [
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
    }

    public function publicWebsiteUrl(): string
    {
        return route('public.project-website.show', ['projectAlias' => $this->getRecord()->alias]);
    }

    public function updatedEventName(): void
    {
        $this->alias = Project::generateUniqueAlias($this->eventName, $this->getRecord()->id);
    }

    public function firstRsvpUrl(): ?string
    {
        $guest = $this->getRecord()->guests()->whereNotNull('rsvp_token')->orderBy('rsvp_number')->first();

        return $guest?->publicRsvpUrl();
    }

    public function colorPalettes(): array
    {
        return [
            'champagne_rose' => [
                'name' => 'Champagne Rose',
                'accent_color' => '#b9838f',
                'background_color' => '#fbf6f1',
                'text_color' => '#3f3434',
                'swatches' => ['#fbf6f1', '#e8c7bd', '#b9838f', '#80656b', '#3f3434'],
            ],
            'tuscan_garden' => [
                'name' => 'Tuscan Garden',
                'accent_color' => '#8f7049',
                'background_color' => '#f7f3ea',
                'text_color' => '#34362b',
                'swatches' => ['#f7f3ea', '#d8c7a3', '#8f7049', '#667255', '#34362b'],
            ],
            'lake_como' => [
                'name' => 'Lake Como',
                'accent_color' => '#5d8893',
                'background_color' => '#f4f7f5',
                'text_color' => '#27363a',
                'swatches' => ['#f4f7f5', '#c9d8d5', '#5d8893', '#b8a57a', '#27363a'],
            ],
            'black_tie' => [
                'name' => 'Black Tie',
                'accent_color' => '#c2a36b',
                'background_color' => '#f8f5ee',
                'text_color' => '#252323',
                'swatches' => ['#f8f5ee', '#d8c7a3', '#c2a36b', '#58534c', '#252323'],
            ],
            'lavender_sage' => [
                'name' => 'Lavender Sage',
                'accent_color' => '#8d7d9c',
                'background_color' => '#f8f6f2',
                'text_color' => '#33362f',
                'swatches' => ['#f8f6f2', '#d8d8c8', '#a9b394', '#8d7d9c', '#33362f'],
            ],
        ];
    }

    public function fontPresets(): array
    {
        return [
            'allura' => 'Allura',
            'parisienne' => 'Parisienne',
            'great_vibes' => 'Great Vibes',
        ];
    }

    public function setPalette(string $palette): void
    {
        $palettes = $this->colorPalettes();

        if (! isset($palettes[$palette])) {
            return;
        }

        $this->website['settings']['palette_preset'] = $palette;
        $this->website['settings']['accent_color'] = $palettes[$palette]['accent_color'];
        $this->website['settings']['background_color'] = $palettes[$palette]['background_color'];
        $this->website['settings']['text_color'] = $palettes[$palette]['text_color'];
    }

    public function uploadHeroImages(): void
    {
        $this->validate([
            'heroImageUploads' => ['required', 'array', 'min:1'],
            'heroImageUploads.*' => ['image', 'max:8192'],
        ]);

        $this->website['home']['hero_images'] ??= [];

        foreach ($this->heroImageUploads as $upload) {
            $this->website['home']['hero_images'][] = [
                'url' => '/storage/' . $upload->store('projects/website/hero', 'public'),
                'caption' => '',
            ];
        }

        $this->heroImageUploads = [];

        Notification::make()
            ->title('Hero images added')
            ->success()
            ->send();
    }

    public function sectionWarnings(?string $section = null): array
    {
        $checks = [
            'home' => [
                'Title' => $this->filled('home.title'),
                'Hero image' => count(array_filter($this->website['home']['hero_images'] ?? [], fn ($item) => filled($item['url'] ?? null))) > 0 || $this->filled('home.hero_image'),
                'Date' => $this->filled('home.date'),
                'Location' => $this->filled('home.location'),
            ],
            'schedule' => ['At least one schedule moment' => count($this->website['schedule']['items'] ?? []) > 0],
            'travel' => [
                'Intro or travel image' => $this->filled('travel.intro') || $this->filled('travel.image'),
                'At least one hotel or transport item' => count($this->website['travel']['hotels'] ?? []) + count($this->website['travel']['transportation'] ?? []) > 0,
            ],
            'registry' => ['Registry text or link' => $this->filled('registry.intro') || $this->filled('registry.url')],
            'wedding_party' => ['At least one person' => count($this->website['wedding_party']['people'] ?? []) > 0],
            'gallery' => ['At least three images' => count(array_filter($this->website['gallery']['images'] ?? [], fn ($item) => filled($item['url'] ?? null))) >= 3],
            'things_to_do' => ['At least one recommendation' => count($this->website['things_to_do']['items'] ?? []) > 0],
            'faqs' => ['At least one question' => count($this->website['faqs']['items'] ?? []) > 0],
            'events' => ['At least one event' => count($this->website['events']['items'] ?? []) > 0],
            'rsvp' => [
                'RSVP section title' => $this->filled('rsvp.title'),
                'At least one guest RSVP link exists' => $this->getRecord()->guests()->whereNotNull('rsvp_token')->exists(),
            ],
        ];

        foreach ($this->tabs() as $key => $label) {
            if ($key !== 'home' && ! (bool) ($this->website[$key]['enabled'] ?? false)) {
                $checks[$key] = [];
            }
        }

        $warnings = collect($checks)->map(fn (array $items): array => collect($items)
            ->filter(fn (bool $ok): bool => ! $ok)
            ->keys()
            ->all()
        )->all();

        return $section ? ($warnings[$section] ?? []) : $warnings;
    }

    public function totalWarnings(): int
    {
        return collect($this->sectionWarnings())->sum(fn (array $items): int => count($items));
    }

    public function addItem(string $section, string $list): void
    {
        $this->website[$section][$list] ??= [];
        $this->website[$section][$list][] = $this->blankItem($section, $list);
    }

    public function removeItem(string $section, string $list, int $index): void
    {
        unset($this->website[$section][$list][$index]);
        $this->website[$section][$list] = array_values($this->website[$section][$list] ?? []);
    }

    public function saveWebsite(): void
    {
        $this->validate([
            'eventName' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
        ]);

        $this->website = $this->normalizeWebsite($this->website);
        $this->eventName = trim($this->eventName);
        $this->alias = Project::generateUniqueAlias($this->alias ?: $this->eventName, $this->getRecord()->id);

        $this->getRecord()->forceFill([
            'name' => $this->eventName,
            'alias' => $this->alias,
            'website_json' => $this->website,
        ])->save();

        Notification::make()
            ->title('Website saved')
            ->success()
            ->send();
    }

    protected function ensureRecordAlias(): void
    {
        if (filled($this->getRecord()->alias)) {
            return;
        }

        $this->getRecord()
            ->forceFill([
                'alias' => Project::generateUniqueAlias($this->getRecord()->name, $this->getRecord()->id),
            ])
            ->saveQuietly();
    }

    protected function normalizeWebsite(array $website): array
    {
        $defaults = Project::defaultWebsiteConfiguration($this->getRecord());
        $providedHeroImages = array_key_exists('hero_images', $website['home'] ?? []);
        $providedHeroImageItems = $providedHeroImages ? ($website['home']['hero_images'] ?? []) : [];
        $website = array_replace_recursive($defaults, $website);

        foreach ($this->tabs() as $section => $label) {
            $website[$section]['enabled'] = (bool) ($website[$section]['enabled'] ?? ($defaults[$section]['enabled'] ?? false));
        }

        if ($providedHeroImages) {
            $website['home']['hero_images'] = array_values(array_filter($providedHeroImageItems, fn ($item): bool => is_array($item) && filled($item['url'] ?? null)));
            $website['home']['hero_image'] = $website['home']['hero_images'][0]['url'] ?? '';
        } elseif (empty($website['home']['hero_images']) && filled($website['home']['hero_image'] ?? null)) {
            $website['home']['hero_images'] = [['url' => $website['home']['hero_image'], 'caption' => '']];
        } elseif (filled($website['home']['hero_image'] ?? null)) {
            $heroImage = $website['home']['hero_image'];
            $hasLegacyHeroImage = collect($website['home']['hero_images'] ?? [])->contains(fn ($item): bool => ($item['url'] ?? null) === $heroImage);

            if (! $hasLegacyHeroImage) {
                array_unshift($website['home']['hero_images'], ['url' => $heroImage, 'caption' => '']);
            }
        }

        foreach (['home.hero_images', 'schedule.items', 'travel.hotels', 'travel.transportation', 'wedding_party.people', 'gallery.images', 'things_to_do.items', 'faqs.items', 'events.items'] as $path) {
            [$section, $list] = explode('.', $path);
            $website[$section][$list] = array_values(array_filter($website[$section][$list] ?? [], fn ($item): bool => is_array($item) && collect($item)->filter(fn ($value) => filled($value))->isNotEmpty()));
        }

        return $website;
    }

    protected function blankItem(string $section, string $list): array
    {
        if ($section === 'faqs') {
            return ['question' => '', 'answer' => ''];
        }

        return match ($list) {
            'items' => ['title' => '', 'date' => '', 'time' => '', 'location' => '', 'address' => '', 'text' => '', 'url' => ''],
            'hotels' => ['name' => '', 'type' => 'Hotel', 'address' => '', 'description' => '', 'discount' => '', 'url' => ''],
            'transportation' => ['title' => '', 'type' => 'Transfer', 'description' => '', 'url' => ''],
            'people' => ['name' => '', 'role' => '', 'image' => '', 'bio' => ''],
            'images' => ['url' => '', 'caption' => ''],
            'hero_images' => ['url' => '', 'caption' => ''],
            default => ['question' => '', 'answer' => ''],
        };
    }

    protected function filled(string $path): bool
    {
        $value = data_get($this->website, $path);

        return filled($value);
    }
}
