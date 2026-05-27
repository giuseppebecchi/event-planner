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

class ManageProjectWebsite extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.manage-project-website';

    protected static ?string $breadcrumb = 'Website';

    protected Width|string|null $maxContentWidth = Width::Full;

    public array $website = [];

    public string $activeTab = 'home';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
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
        return route('public.project-website.show', ['project' => $this->getRecord()]);
    }

    public function firstRsvpUrl(): ?string
    {
        $guest = $this->getRecord()->guests()->whereNotNull('rsvp_token')->orderBy('rsvp_number')->first();

        return $guest?->publicRsvpUrl();
    }

    public function sectionWarnings(?string $section = null): array
    {
        $checks = [
            'home' => [
                'Title' => $this->filled('home.title'),
                'Hero image' => $this->filled('home.hero_image'),
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
        $this->website = $this->normalizeWebsite($this->website);

        $this->getRecord()->forceFill([
            'website_json' => $this->website,
        ])->save();

        Notification::make()
            ->title('Website saved')
            ->success()
            ->send();
    }

    protected function normalizeWebsite(array $website): array
    {
        $defaults = Project::defaultWebsiteConfiguration($this->getRecord());
        $website = array_replace_recursive($defaults, $website);

        foreach ($this->tabs() as $section => $label) {
            $website[$section]['enabled'] = (bool) ($website[$section]['enabled'] ?? ($defaults[$section]['enabled'] ?? false));
        }

        foreach (['schedule.items', 'travel.hotels', 'travel.transportation', 'wedding_party.people', 'gallery.images', 'things_to_do.items', 'faqs.items', 'events.items'] as $path) {
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
            default => ['question' => '', 'answer' => ''],
        };
    }

    protected function filled(string $path): bool
    {
        $value = data_get($this->website, $path);

        return filled($value);
    }
}
