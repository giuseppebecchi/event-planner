<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LeadProposalPdfController extends Controller
{
    public function __invoke(Lead $lead)
    {
        $lead->loadMissing('project');

        $pdf = Pdf::loadView('pdf.lead-proposal', [
            'lead' => $lead,
            'data' => $this->buildData($lead),
            'images' => $this->proposalImages(),
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
        $extraRows = $this->normalizedRows($lead->budget_wedding_planner_extra_services);
        $mainOffer = $plannerRows[0] ?? null;
        $mainFee = $this->money($mainOffer['amount'] ?? null);

        return [
            'couple_name' => $lead->couple_name ?: trim(collect([$project?->partner_one_name, $project?->partner_two_name])->filter()->implode(' & ')),
            'proposal_title' => Str::upper(sprintf("Wedding in %s\n%s", $region, $period)),
            'offer_title' => Str::upper($this->mainOfferLabel($mainOffer, $guestText)),
            'main_fee' => $mainFee ?: $this->money($project?->budget_amount ?? $lead->budget_amount) ?: 'Tuscany: 6900 euros',
            'planning_rows_left' => $this->planningRowsLeft(),
            'planning_rows_right' => $this->planningRowsRight(),
            'extra_rows' => $extraRows ?: $this->defaultExtraRows(),
            'valid_until' => now()->addDays(30)->format('F jS Y'),
            'confirmation_rows' => $this->confirmationRows(),
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

    protected function confirmationRows(): array
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

    protected function normalizedRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(fn (array $row): array => [
                'label' => trim((string) ($row['label'] ?? '')),
                'amount' => $row['amount'] ?? null,
            ])
            ->filter(fn (array $row): bool => $row['label'] !== '' || filled($row['amount']))
            ->values()
            ->all();
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

    protected function proposalImages(): array
    {
        return collect([
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
        ])->map(fn (string $path): string => public_path($path))->all();
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
