<?php

namespace App\Support;

use App\Models\Config;
use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeadContractPdfRenderer
{
    public function output(Lead $lead): string
    {
        return $this->make($lead)->output();
    }

    public function filename(Lead $lead): string
    {
        $name = Str::slug($lead->couple_name ?: 'contract');

        return sprintf('%s-fairytale-italy-weddings-contract.pdf', $name);
    }

    protected function make(Lead $lead)
    {
        $lead->loadMissing('project', 'venueRecord');

        $pdf = Pdf::loadView('pdf.lead-contract', [
            'lead' => $lead,
            'contentHtml' => $this->contractContent($lead),
            'summary' => $this->summary($lead),
            'images' => $this->images(),
            'fonts' => $this->fonts(),
        ])->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $dompdf->getCanvas()->page_text(482, 790, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7, [0.12, 0.18, 0.16]);

        return $pdf;
    }

    protected function contractContent(Lead $lead): string
    {
        $html = trim((string) $lead->contract_content);

        if ($html === '') {
            $html = '<h1>Wedding Planner Contract Fairytale Italy Weddings</h1><p>No contract content has been written yet.</p>';
        }

        return $this->replacePlaceholders($html, $lead);
    }

    protected function summary(Lead $lead): array
    {
        $project = $lead->project;

        return [
            'couple' => $lead->couple_name ?: ($project?->coupleNames() ?: '') ?: 'Client',
            'date' => $this->formatWeddingDate($lead->wedding_date, $lead->wedding_period) ?: 'To be confirmed',
            'location' => $lead->venueDisplayName() ?: $lead->desired_region ?: 'Italy',
            'issued_at' => now()->format('F jS Y'),
        ];
    }

    public function replacePlaceholders(string $content, Lead $lead): string
    {
        $project = $lead->project;
        $venueName = $lead->venueDisplayName();
        $venueLocality = $lead->venueDisplayLocality();

        $mainContactName = trim(collect([$lead->first_name, $lead->last_name])->filter()->implode(' '));
        $secondaryContactName = trim(collect([$lead->secondary_first_name, $lead->secondary_last_name])->filter()->implode(' '));

        $values = [
            'couple_name' => $lead->couple_name ?: trim(collect([$mainContactName, $secondaryContactName])->filter()->implode(' & ')),
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'nationality' => $lead->nationality,
            'city' => $lead->city,
            'secondary_first_name' => $lead->secondary_first_name,
            'secondary_last_name' => $lead->secondary_last_name,
            'secondary_email' => $lead->secondary_email,
            'secondary_phone' => $lead->secondary_phone,
            'estimated_guest_count' => $lead->estimated_guest_count,
            'wedding_period' => $lead->wedding_period,
            'wedding_date' => $this->formatWeddingDate($lead->wedding_date, $lead->wedding_period),
            'desired_region' => $lead->desired_region,
            'ceremony_type' => $lead->ceremony_type,
            'ceremony_details' => $lead->ceremony_details,
            'location_request_type' => $lead->location_request_type,
            'venue' => $venueName,
            'ceremony_location' => $lead->ceremony_location,
            'estimated_timings' => $lead->estimated_timings,
            'additional_events' => $lead->additional_events,
            'budget_amount' => $this->formatMoney($lead->budget_amount),
            'style_description' => $lead->style_description,
            'proposal_sent_at' => $this->formatDate($lead->proposal_sent_at),
            'contract_sent_at' => $this->formatDate($lead->contract_sent_at) ?: now()->format('F jS Y'),
            'contract_received_at' => $this->formatDate($lead->contract_received_at),
            'internal_notes' => $lead->internal_notes,
            'name' => $project?->name,
            'project_first_name' => $project?->first_name,
            'project_last_name' => $project?->last_name,
            'project_email' => $project?->email,
            'project_phone' => $project?->phone,
            'project_city' => $project?->city,
            'project_secondary_first_name' => $project?->secondary_first_name,
            'project_secondary_last_name' => $project?->secondary_last_name,
            'project_secondary_email' => $project?->secondary_email,
            'project_secondary_phone' => $project?->secondary_phone,
            'partner_one_name' => $mainContactName,
            'partner_two_name' => $secondaryContactName,
            'reference_email' => $lead->email,
            'primary_phone' => $lead->phone,
            'address' => $lead->address,
            'private_notes' => $lead->internal_notes,
            'region' => $lead->desired_region,
            'locality' => $venueLocality ?: $lead->desired_region,
            'event_start_date' => $this->formatWeddingDate($lead->wedding_date, $lead->wedding_period),
            'event_end_date' => null,
            'final_guest_count' => null,
            'status' => $project?->status,
            'reception_location' => $venueName,
            'contract_total_fee' => $this->contractTotalFee($lead),
            'contract_first_deposit' => null,
            'contract_second_deposit_due_at' => null,
            'contract_second_deposit' => null,
            'contract_balance_due_at' => null,
            'contract_balance' => null,
            'force_majeure_credit_until' => $this->forceMajeureCreditUntil($project?->event_start_date),
        ];

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_-]+)\s*}}/', function (array $matches) use ($values): string {
            $key = $matches[1];
            $value = $values[$key] ?? null;

            if ($value === null || $value === '') {
                $configHtml = $this->configPlaceholderHtml($key);

                if ($configHtml !== null) {
                    return $configHtml;
                }

                return '<span class="missing-value">To be confirmed</span>';
            }

            return e((string) $value);
        }, $content) ?? $content;
    }

    protected function configPlaceholderHtml(string $slug): ?string
    {
        $config = Config::query()
            ->where('slug', $slug)
            ->first();

        if (! $config) {
            return null;
        }

        if ($config->type === Config::TYPE_IMAGE && filled($config->img)) {
            $path = Storage::disk('public')->path((string) $config->img);

            if (! is_file($path)) {
                return null;
            }

            return sprintf(
                '<img src="%s" alt="%s" style="width:45mm;height:auto;">',
                e($path),
                e($config->label),
            );
        }

        if ($config->type === Config::TYPE_TEXT && filled($config->text)) {
            return e((string) $config->text);
        }

        return null;
    }

    protected function contractTotalFee(Lead $lead): ?string
    {
        $plannerRows = is_array($lead->budget_wedding_planner) ? $lead->budget_wedding_planner : [];
        $plannerTotal = collect($plannerRows)
            ->sum(fn (array $row): float => $this->numericAmount($row['amount'] ?? null));

        $selectedExtrasTotal = collect([
            ...$this->selectedBudgetRows($lead->budget_wedding_planner_extra_services),
            ...$this->selectedBudgetRows($lead->budget_wedding_planner_special_packages),
        ])->sum(fn (array $row): float => $this->numericAmount($row['amount'] ?? null));

        $contractTotal = $plannerTotal + $selectedExtrasTotal;

        return $this->formatMoney($contractTotal > 0 ? $contractTotal : ($lead->project?->budget_amount ?? $lead->budget_amount));
    }

    protected function selectedBudgetRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn (array $row): bool => (bool) ($row['add_to_budget'] ?? false))
            ->values()
            ->all();
    }

    protected function forceMajeureCreditUntil(mixed $weddingDate): ?string
    {
        if (! $weddingDate) {
            return null;
        }

        return Carbon::parse($weddingDate)->addYear()->format('F jS Y');
    }

    protected function formatDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('F jS Y');
    }

    protected function formatWeddingDate(mixed $value, ?string $fallback = null): ?string
    {
        if (! $value) {
            return $fallback;
        }

        $date = (string) $value;

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        return Carbon::createFromFormat('Y-m-d', $date)->format('M d, Y');
    }

    protected function formatMoney(mixed $value): ?string
    {
        $amount = $this->numericAmount($value);

        if ($amount <= 0) {
            return null;
        }

        return number_format($amount, 0, ',', '.').' euros';
    }

    protected function numericAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    protected function images(): array
    {
        return collect([
            'logo' => 'images/proposal/client-logo.png',
        ])->map(fn (string $path): string => public_path($path))->all();
    }

    protected function fonts(): array
    {
        return [
            'title' => public_path('fonts/proposal/Montserrat-Regular.ttf'),
        ];
    }
}
