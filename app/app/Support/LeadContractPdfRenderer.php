<?php

namespace App\Support;

use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
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
        $lead->loadMissing('project');

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
            'couple' => $lead->couple_name ?: trim(collect([$project?->partner_one_name, $project?->partner_two_name])->filter()->implode(' & ')) ?: 'Client',
            'date' => $this->formatDate($project?->event_start_date) ?: $lead->wedding_period ?: 'To be confirmed',
            'location' => $project?->locality ?: $project?->region ?: $lead->desired_region ?: 'Italy',
            'issued_at' => now()->format('F jS Y'),
        ];
    }

    public function replacePlaceholders(string $content, Lead $lead): string
    {
        $project = $lead->project;

        $values = [
            'couple_name' => $lead->couple_name ?: trim(collect([$project?->partner_one_name, $project?->partner_two_name])->filter()->implode(' & ')),
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'nationality' => $lead->nationality,
            'estimated_guest_count' => $project?->estimated_guest_count ?? $lead->estimated_guest_count,
            'wedding_period' => $lead->wedding_period,
            'desired_region' => $lead->desired_region,
            'ceremony_type' => $lead->ceremony_type,
            'ceremony_details' => $lead->ceremony_details,
            'location_request_type' => $lead->location_request_type,
            'additional_events' => $lead->additional_events,
            'budget_amount' => $this->formatMoney($project?->budget_amount ?? $lead->budget_amount),
            'style_description' => $lead->style_description,
            'proposal_sent_at' => $this->formatDate($lead->proposal_sent_at),
            'contract_sent_at' => $this->formatDate($lead->contract_sent_at) ?: now()->format('F jS Y'),
            'contract_received_at' => $this->formatDate($lead->contract_received_at),
            'internal_notes' => $lead->internal_notes,
            'name' => $project?->name,
            'partner_one_name' => $project?->partner_one_name ?: $lead->first_name,
            'partner_two_name' => $project?->partner_two_name ?: $lead->last_name,
            'reference_email' => $project?->reference_email ?: $lead->email,
            'primary_phone' => $project?->primary_phone ?: $lead->phone,
            'secondary_phone' => $project?->secondary_phone,
            'address' => $project?->address,
            'private_notes' => $project?->private_notes,
            'region' => $project?->region ?: $lead->desired_region,
            'locality' => $project?->locality,
            'event_start_date' => $this->formatDate($project?->event_start_date),
            'event_end_date' => $this->formatDate($project?->event_end_date),
            'final_guest_count' => $project?->final_guest_count,
            'status' => $project?->status,
            'wedding_date' => $this->formatDate($project?->event_start_date),
            'ceremony_location' => $project?->locality,
            'reception_location' => $project?->locality,
            'estimated_timings' => null,
            'contract_total_fee' => $this->contractTotalFee($lead),
            'contract_first_deposit' => null,
            'contract_second_deposit_due_at' => null,
            'contract_second_deposit' => null,
            'contract_balance_due_at' => null,
            'contract_balance' => null,
            'force_majeure_credit_until' => $this->forceMajeureCreditUntil($project?->event_start_date),
        ];

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_]+)\s*}}/', function (array $matches) use ($values): string {
            $value = $values[$matches[1]] ?? null;

            if ($value === null || $value === '') {
                return '<span class="missing-value">To be confirmed</span>';
            }

            return e((string) $value);
        }, $content) ?? $content;
    }

    protected function contractTotalFee(Lead $lead): ?string
    {
        $plannerRows = is_array($lead->budget_wedding_planner) ? $lead->budget_wedding_planner : [];
        $firstAmount = collect($plannerRows)->pluck('amount')->filter(fn ($amount): bool => filled($amount) && (float) $amount > 0)->first();

        return $this->formatMoney($firstAmount ?? $lead->project?->budget_amount ?? $lead->budget_amount);
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

    protected function formatMoney(mixed $value): ?string
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
