<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Template;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeadProposalPdfController extends Controller
{
    protected const PROPOSAL_TEMPLATE_TUSCANY = 'tuscany';
    protected const PROPOSAL_TEMPLATE_LAKE_COMO = 'lake_como';

    public function __invoke(Lead $lead)
    {
        $lead->loadMissing('project');

        $pdf = Pdf::loadView('pdf.lead-proposal', [
            'lead' => $lead,
            'data' => $this->buildData($lead),
            'images' => $this->proposalImages($lead),
            'fonts' => $this->proposalFonts(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename($lead));
    }

    protected function buildData(Lead $lead): array
    {
        $project = $lead->project;
        $region = $project?->region ?: $lead->desired_region ?: 'Tuscany';
        $period = $lead->wedding_period ?: $this->formatDate($project?->event_start_date) ?: 'September 2027';
        $guestText = $this->guestText($lead);
        $plannerRows = $this->normalizedRows($lead->budget_wedding_planner);
        $extraRows = [
            ...$this->normalizedRows($lead->budget_wedding_planner_extra_services, onlyPositive: true),
            ...$this->normalizedRows($lead->budget_wedding_planner_special_packages, onlyPositive: true),
        ];
        $mainOffer = $plannerRows[0] ?? null;
        $mainFee = $this->money($mainOffer['amount'] ?? null);
        $planningRows = $this->planningRows($lead);

        return [
            'couple_name' => $lead->couple_name ?: ($project?->coupleNames() ?: ''),
            'proposal_title' => $this->proposalTitle($lead, $region, $period),
            'offer_title' => Str::upper($this->mainOfferLabel($mainOffer, $guestText)),
            'main_fee' => $mainFee ?: $this->money($project?->budget_amount ?? $lead->budget_amount) ?: $this->defaultMainFee($lead),
            'planning_rows_left' => $planningRows->get(0, []),
            'planning_rows_right' => $planningRows->get(1, []),
            'extra_rows' => $extraRows ?: $this->defaultExtraRows(),
            'valid_until' => now()->addDays(30)->format('F jS Y'),
            'confirmation_rows' => $this->confirmationRows($lead),
        ];
    }

    protected function mainOfferLabel(?array $row, string $guestText): string
    {
        $label = trim((string) ($row['label'] ?? ''));

        if ($label !== '') {
            return wordwrap($label, 28, "\n", false);
        }

        return sprintf(
            "Planning and coordination fee for\nwedding day + welcome dinner + recovery event\n%s",
            $guestText,
        );
    }

    protected function planningRows(Lead $lead): \Illuminate\Support\Collection
    {
        $rows = $this->proposalWeddingPlanningServiceRows($lead);

        if ($rows->isEmpty()) {
            $rows = collect([
                ...$this->planningRowsLeft(),
                ...$this->planningRowsRight(),
            ]);
        }

        return $rows
            ->values()
            ->chunk((int) ceil(max(1, $rows->count()) / 2))
            ->values();
    }

    protected function proposalWeddingPlanningServiceRows(Lead $lead): \Illuminate\Support\Collection
    {
        $content = trim((string) ($lead->proposal_wedding_planning_service ?: Template::query()
            ->where('slug', 'proposal-wedding-planning-service')
            ->where('language', 'en')
            ->value('content')));

        if ($content === '') {
            return collect();
        }

        preg_match_all('/<(?:p|li)[^>]*>(.*?)<\/(?:p|li)>/is', $content, $matches);

        $rows = collect($matches[1] ?? [])
            ->map(fn (string $row): string => trim(html_entity_decode(strip_tags($row))))
            ->filter();

        if ($rows->isNotEmpty()) {
            return $rows->values();
        }

        return collect(preg_split('/\r\n|\r|\n/', strip_tags($content)) ?: [])
            ->map(fn (string $row): string => trim(html_entity_decode($row)))
            ->filter()
            ->values();
    }

    protected function planningRowsLeft(): array
    {
        return [
            'Unlimited email correspondence and 3 consultation with Zoom video calls when needed after commitment',
            'Venue research based on your requirements',
            'Suggestion of talented and trusted vendors (florists, musicians, dj, photographers, videographers, drivers, lighting companies, celebrants...), correspondence, assistance and coordination with the hired ones',
            'Recommendations for the site decor, food, favours, centrepieces, wedding stationery, welcome gifts etc',
            'Coordination of rental items and furnishings including custom lightings and tents if needed',
            'On-site sessions for menu tasting, make-up/hair trials and briefings/calls with involved vendors before the event (with or without the couple)',
            'Creation of a wedding schedule/timeline for you and for every vendor to be delivered 10/15 days prior to the wedding',
        ];
    }

    protected function planningRowsRight(): array
    {
        return [
            'Wedding day coordination and supervision of 1 main planner and 2 assistants from bridal getting ready until midnight.',
            'Symbolic ceremony organization',
            'Planning and coordination of welcome event and recovery event',
            'Reminder of due payments',
            'Follow-up with every vendor 3-5 days before the wedding',
            'Continuous support during the organization of your big day',
        ];
    }

    protected function confirmationRows(Lead $lead): array
    {
        $configuredRows = $this->proposalConditionRows($lead);

        if ($configuredRows !== []) {
            return $configuredRows;
        }

        return $this->defaultConfirmationRows();
    }

    protected function proposalConditionRows(Lead $lead): array
    {
        $content = trim((string) $lead->proposal_content);

        if ($content === '') {
            return [];
        }

        preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $content, $matches);

        $rows = collect($matches[1] ?? [])
            ->map(fn (string $row): string => trim(html_entity_decode(strip_tags($row))))
            ->filter()
            ->values()
            ->all();

        if ($rows !== []) {
            return $rows;
        }

        return collect(preg_split('/\r\n|\r|\n/', strip_tags($content)) ?: [])
            ->map(fn (string $row): string => trim(html_entity_decode($row)))
            ->filter()
            ->values()
            ->all();
    }

    protected function defaultConfirmationRows(): array
    {
        return [
            'A non-refundable deposit of 2000 euros is required upon confirmation of the wedding planning package.',
            'Second deposit of 4000 euros is due 6 months before the wedding day.',
            'Balance is due by the wedding day.',
            'Payments are accepted in cash, Wise or bank transfer.',
            'In case of postponements due to Covid-19 or force majeur, deposits paid will be used as credit to reschedule the event.',
            'Our travel fees are included on us for maximum 2 trips to the designated region/venue (usually for site inspections, meetings with the couple/suppliers and for the wedding day). For additional trips, extra travel fees apply.',
            'During the event(s), Staff meals and water are required for the planner and assistant(s).',
        ];
    }

    protected function defaultExtraRows(): array
    {
        return [
            ['label' => 'Management of guests accommodation out of the venue', 'amount' => 300],
            ['label' => 'Extra guests on the wedding day: extra every 10 guests', 'amount' => 100],
            ['label' => 'Help with guests transfers on the wedding day', 'amount' => 300],
            ['label' => 'Extra coordinator/hostess if needed: from each per day', 'amount' => 250],
            ['label' => 'Second venue research', 'amount' => 500],
            ['label' => 'Extra pre and post wedding events (planning and coordination): each', 'amount' => 800],
            ['label' => 'Extra video calls: each', 'amount' => 50],
        ];
    }

    protected function normalizedRows(mixed $rows, bool $onlyPositive = false): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(fn (array $row): array => [
                'label' => trim((string) ($row['label'] ?? '')),
                'amount' => $this->numericAmount($row['amount'] ?? null),
            ])
            ->filter(fn (array $row): bool => $row['label'] !== '' || $row['amount'] > 0)
            ->when($onlyPositive, fn ($rows) => $rows->filter(fn (array $row): bool => $this->numericAmount($row['amount'] ?? null) > 0))
            ->values()
            ->all();
    }

    protected function numericAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    protected function guestText(Lead $lead): string
    {
        $count = $lead->project?->estimated_guest_count ?? $lead->estimated_guest_count;

        if (! $count) {
            return 'From 70 to max 100 guests';
        }

        return sprintf('For up to %s guests', $count);
    }

    protected function money(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $amount = (float) $value;

        if ($amount <= 0) {
            return null;
        }

        return number_format($amount, 0, ',', '.').' euros';
    }

    protected function formatDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('F Y');
    }

    protected function proposalTitle(Lead $lead, string $region, string $period): string
    {
        if ($this->proposalTemplate($lead) === self::PROPOSAL_TEMPLATE_LAKE_COMO) {
            return Str::upper(sprintf("Wedding Lake Como\n%s", $period));
        }

        return Str::upper(sprintf("Wedding in %s\n%s", $region, $period));
    }

    protected function defaultMainFee(Lead $lead): string
    {
        return $this->proposalTemplate($lead) === self::PROPOSAL_TEMPLATE_LAKE_COMO
            ? 'Lake Como: 6900 euros'
            : 'Tuscany: 6900 euros';
    }

    protected function proposalImages(Lead $lead): array
    {
        $defaults = [
            'logo' => 'images/proposal/client-logo.png',
            'social_block' => 'images/proposal/social-block.png',
            'cover_bride' => 'images/proposal/cover-bride.png',
            'cover_venue' => 'images/proposal/cover-venue.png',
            'table_cypress' => 'images/proposal/table-cypress.png',
            'ceremony_hills' => 'images/proposal/ceremony-hills.png',
            'ceremony_view' => 'images/proposal/ceremony-view.png',
            'ceremony_altar' => 'images/proposal/ceremony-altar.png',
            'dinner_garden' => 'images/proposal/dinner-garden.png',
            'table_white' => 'images/proposal/table-white.png',
            'table_strip' => 'images/proposal/table-strip.png',
            'wedding_ceremony' => 'images/proposal/wedding-ceremony.png',
            'olive_ceremony' => 'images/proposal/olive-ceremony.png',
            'table_film' => 'images/proposal/table-film.png',
            'table_rustic' => 'images/proposal/table-rustic.png',
        ];

        $config = is_array($lead->proposal_images_json_config) ? $lead->proposal_images_json_config : [];

        return collect($defaults)
            ->map(function (string $defaultPath, string $key) use ($config, $lead): string {
                if (in_array($key, ['logo', 'social_block'], true)) {
                    return public_path($defaultPath);
                }

                $customPath = $this->customProposalImagePath($config[$key] ?? null);

                return $customPath ?: public_path($this->defaultProposalImagePath($lead, basename($defaultPath)));
            })
            ->all();
    }

    protected function proposalTemplate(Lead $lead): string
    {
        $config = is_array($lead->proposal_images_json_config) ? $lead->proposal_images_json_config : [];
        $template = (string) ($config['template'] ?? self::PROPOSAL_TEMPLATE_TUSCANY);

        return in_array($template, [self::PROPOSAL_TEMPLATE_TUSCANY, self::PROPOSAL_TEMPLATE_LAKE_COMO], true)
            ? $template
            : self::PROPOSAL_TEMPLATE_TUSCANY;
    }

    protected function defaultProposalImagePath(Lead $lead, string $filename): string
    {
        if ($this->proposalTemplate($lead) === self::PROPOSAL_TEMPLATE_LAKE_COMO) {
            $lakeComoPath = 'images/proposal/lake-como/'.$filename;

            if (is_file(public_path($lakeComoPath))) {
                return $lakeComoPath;
            }
        }

        return 'images/proposal/'.$filename;
    }

    protected function customProposalImagePath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)->filter()->first();
        }

        if (blank($value)) {
            return null;
        }

        $path = (string) $value;

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }

    protected function proposalFonts(): array
    {
        return [
            'title' => public_path('fonts/proposal/Montserrat-Regular.ttf'),
        ];
    }

    protected function filename(Lead $lead): string
    {
        $name = Str::slug($lead->couple_name ?: 'proposal');

        return sprintf('%s-fairytale-italy-weddings-proposal.pdf', $name);
    }
}
