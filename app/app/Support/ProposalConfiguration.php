<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\ProposalTemplate;
use App\Models\Template;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProposalConfiguration
{
    public static function textFields(): array
    {
        return [
            'cover_title' => 'Cover title',
            'cover_subtitle' => 'Cover subtitle',
            'contact' => 'Contact information',
            'prices_heading' => 'Prices heading',
            'service_heading' => 'Service heading',
            'planning_service' => 'Wedding planning service — left column',
            'planning_service_right' => 'Wedding planning service — right column',
            'proposal_title' => 'Proposal title',
            'proposal_subtitle' => 'Proposal subtitle',
            'extras_heading' => 'Extra services heading',
            'vat_note' => 'VAT note',
            'confirmation_heading' => 'Confirmation heading',
            'conditions' => 'Conditions and offer validity',
        ];
    }

    public static function imageFields(): array
    {
        return [
            'cover_bride' => [1, 'Cover left photo', 'cover-bride.png'],
            'cover_venue' => [1, 'Cover right photo', 'cover-venue.png'],
            'logo' => [1, 'Logo', 'client-logo.png'],
            'social_block' => [1, 'Social block', 'social-block.png'],
            'table_cypress' => [2, 'Prices top photo', 'table-cypress.png'],
            'ceremony_hills' => [2, 'Prices right photo', 'ceremony-hills.png'],
            'ceremony_view' => [3, 'Extra services top left', 'ceremony-view.png'],
            'dinner_garden' => [3, 'Extra services bottom left', 'dinner-garden.png'],
            'ceremony_altar' => [3, 'Extra services top centre', 'ceremony-altar.png'],
            'table_white' => [3, 'Extra services bottom centre', 'table-white.png'],
            'table_strip' => [3, 'Extra services top right', 'table-strip.png'],
            'wedding_ceremony' => [4, 'Confirmation top left', 'wedding-ceremony.png'],
            'olive_ceremony' => [4, 'Confirmation bottom left', 'olive-ceremony.png'],
            'table_film' => [4, 'Confirmation top centre', 'table-film.png'],
            'table_rustic' => [4, 'Confirmation bottom centre', 'table-rustic.png'],
        ];
    }

    public static function defaults(string $variant = 'tuscany'): array
    {
        $images = [];
        foreach (self::imageFields() as $key => [$page, $label, $filename]) {
            $lakePath = 'images/proposal/lake-como/'.$filename;
            $images[$key] = $variant === 'lake_como' && is_file(public_path($lakePath))
                ? $lakePath : 'images/proposal/'.$filename;
        }

        return [
            'source_variant' => $variant,
            'cover_title' => '<p>Fairytale<br>Italy<br>Weddings</p>',
            'cover_subtitle' => '<p>Where dreams come true</p>',
            'contact' => '<p>Florence, Italy<br>+39 353 4171643<br>hello@fairytaleitalyweddings.com<br>www.fairytaleitalyweddings.com</p>',
            'prices_heading' => '<p>Our prices</p>',
            'service_heading' => '<p>Wedding planning service</p>',
            ...self::splitPlanningService((string) Template::where('slug', 'proposal-wedding-planning-service')->where('language', 'en')->value('content')),
            'proposal_title' => $variant === 'lake_como' ? '<p>Wedding Lake Como</p>' : '<p>Wedding in [REGION]</p>',
            'proposal_subtitle' => '<p>[MONTH] [YYYY]</p>',
            'extras_heading' => '<p>Extra services:</p>',
            'vat_note' => '<p>All the prices include VAT</p>',
            'confirmation_heading' => '<p>To proceed with the<br>confirmation</p>',
            'conditions' => self::conditionsFromLegacy((string) Template::where('slug', 'proposal')->where('language', 'en')->value('content')),
            'conditions_format' => 'rich-text',
            'images' => $images,
        ];
    }

    public static function forLead(Lead $lead): array
    {
        $config = [
            ...($lead->proposal_layout ?? []),
            'planning_service' => $lead->proposal_wedding_planning_service ?? '',
            'conditions' => $lead->proposal_content ?? '',
        ];

        return array_key_exists('planning_service_right', $config) ? $config : [...$config, ...self::splitPlanningService($config['planning_service'])];
    }

    /** Copy a template once. Existing drafts retain their independently saved text and photos. */
    public static function apply(Lead $lead, ProposalTemplate $template, bool $preserveDraft, ?callable $resolveText = null): void
    {
        abort_if($lead->proposal_sent_at && ! $lead->proposal_layout, 403, 'Sent legacy proposals cannot be converted.');
        abort_unless($template->is_active, 422, 'This proposal template is inactive.');
        $config = $template->configuration;
        if (! array_key_exists('planning_service_right', $config)) {
            $config = [...$config, ...self::splitPlanningService($config['planning_service'] ?? '')];
        }
        $service = $config['planning_service'] ?? '';
        $conditions = $config['conditions'] ?? '';
        if ($resolveText) {
            $service = $resolveText($service);
            $conditions = $resolveText($conditions);
        }
        unset($config['planning_service'], $config['conditions']);
        if ($preserveDraft) {
            if ($lead->proposal_wedding_planning_service) {
                $existing = self::forLead($lead);
                $service = $existing['planning_service'];
                $config['planning_service_right'] = $existing['planning_service_right'];
            }
            $conditions = $lead->proposal_content ?: $conditions;
            foreach (self::imageFields() as $key => $definition) {
                $path = $lead->proposal_images_json_config[$key] ?? null;
                if (is_string($path) && self::imagePath($path)) {
                    $config['images'][$key] = $path;
                }
            }
        }
        if (($preserveDraft && $lead->proposal_content)
            ? ! $lead->proposal_layout
            : ($config['conditions_format'] ?? null) !== 'rich-text') {
            $conditions = self::conditionsFromLegacy($conditions);
        }
        $config['conditions_format'] = 'rich-text';
        // Match the current proposal generation: validity placeholders are resolved on creation.
        $conditions = str_replace('{{ proposal_valid_until }}', now()->addDays(30)->format('F jS Y'), $conditions);
        $lead->forceFill([
            'proposal_template_id' => $template->id,
            'proposal_layout' => $config,
            'proposal_wedding_planning_service' => RichEditorHtmlNormalizer::normalizeListItems($service),
            'proposal_content' => RichEditorHtmlNormalizer::normalizeListItems(str_replace('WEDDING PLANNING SERVICE', $service, $conditions)),
        ])->save();
    }

    /** One-time import of the text actually printed by the legacy conditions layout. */
    public static function conditionsFromLegacy(string $content): string
    {
        $sections = preg_split('/<h[1-6][^>]*>\s*Offer\s+validity\s*<\/h[1-6]>/is', $content, 2) ?: [$content];
        $rows = [];
        foreach ($sections as $index => $section) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $section, $listMatches);
            if ($index === 0 && ! empty($listMatches[1])) {
                $parts = $listMatches[1];
            } else {
                preg_match_all('/<(?:p|li)[^>]*>(.*?)<\/(?:p|li)>/is', $section, $matches);
                $parts = $matches[1] ?: preg_split('/\r\n|\r|\n/', strip_tags($section));
            }
            foreach ($parts as $part) {
                $part = preg_replace('/<br\s*\/?\s*>/i', ' ', $part);
                $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($part), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if ($text !== '') {
                    $rows[] = '<p>'.e($text).'</p>';
                }
            }
        }

        return implode('', $rows);
    }

    /** Preserve the legacy visible midpoint once; subsequent column edits never reflow. */
    public static function splitPlanningService(string $content): array
    {
        preg_match_all('/<(?:p|li)[^>]*>(.*?)<\/(?:p|li)>/is', $content, $matches);
        $parts = $matches[1] ?: preg_split('/\r\n|\r|\n/', strip_tags($content));
        $rows = collect($parts)->map(fn ($row) => trim(html_entity_decode(strip_tags($row))))->filter()->values();
        $midpoint = (int) ceil($rows->count() / 2);
        $html = fn ($items) => $items->map(fn ($text) => '<p>'.e($text).'</p>')->implode('');

        return ['planning_service' => $html($rows->take($midpoint)), 'planning_service_right' => $html($rows->slice($midpoint))];
    }

    public static function imagePath(?string $path): ?string
    {
        if (! $path || str_contains($path, '..') || str_contains($path, '\\')) {
            return null;
        }
        if (str_starts_with($path, 'images/proposal/')) {
            return is_file(public_path($path)) ? public_path($path) : null;
        }
        if (! Str::startsWith($path, ['proposal-templates/', 'leads/proposal-images/'])) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? Storage::disk('public')->path($path) : null;
    }

    public static function replaceVariables(string $text, Lead $lead): string
    {
        $date = $lead->wedding_date ?: $lead->project?->event_start_date;
        $year = '[YYYY]';
        $month = '[MONTH]';
        if (! $date && preg_match('/\b(20\d{2})\b/', (string) $lead->wedding_period, $match)) {
            $year = $match[1];
            if (preg_match('/\b(January|February|March|April|May|June|July|August|September|October|November|December)\b/i', $lead->wedding_period, $match)) {
                $month = strtoupper($match[1]);
            }
        }
        if ($date) {
            $date = Carbon::parse($date)->locale('en');
            $month = strtoupper($date->translatedFormat('F'));
            $year = $date->format('Y');
        }

        return strtr($text, [
            '[MONTH]' => $month,
            '[YYYY]' => $year,
            '[REGION]' => e($lead->project?->region ?: $lead->desired_region ?: 'Tuscany'),
            '[VENUE]' => e($lead->venueDisplayName() ?: ''),
            '[COUPLE]' => e($lead->couple_name ?: ''),
            '{{ proposal_valid_until }}' => now()->addDays(30)->format('F jS Y'),
        ]);
    }
}
