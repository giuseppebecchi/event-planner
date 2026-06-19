<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class LeadBudgetPdfController extends Controller
{
    public function __invoke(Lead $lead)
    {
        $lead->loadMissing('project');

        $pdf = Pdf::loadView('pdf.lead-budget', [
            'lead' => $lead,
            'summary' => $this->summary($lead),
            'sections' => $this->sections($lead),
            'grandTotal' => $this->grandTotal($lead),
            'money' => fn (mixed $amount): string => $this->money($amount),
        ])->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $dompdf->getCanvas()->page_text(470, 810, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7, [0.33, 0.27, 0.22]);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($lead).'"',
        ]);
    }

    protected function summary(Lead $lead): array
    {
        $project = $lead->project;

        return [
            'couple' => $lead->couple_name ?: trim(collect([$project?->partner_one_name, $project?->partner_two_name])->filter()->implode(' & ')) ?: 'Client',
            'email' => $project?->reference_email ?: $lead->email,
            'phone' => $project?->primary_phone ?: $lead->phone,
            'date' => $project?->event_start_date?->format('F jS Y') ?: $lead->wedding_period ?: 'To be confirmed',
            'location' => $project?->locality ?: $project?->region ?: $lead->desired_region ?: 'Italy',
            'guests' => $project?->estimated_guest_count ?? $lead->estimated_guest_count,
            'client_budget' => $project?->budget_amount ?? $lead->budget_amount,
            'issued_at' => now()->format('F jS Y'),
        ];
    }

    protected function sections(Lead $lead): array
    {
        return [
            [
                'title' => 'Estimated vendor budget',
                'description' => 'Expected third-party vendor costs by category.',
                'rows' => $this->normalizedRows($lead->budget_vendors, true),
            ],
            [
                'title' => 'Wedding planner fee',
                'description' => 'Planning and coordination fee for the wedding services described in the proposal.',
                'rows' => $this->normalizedRows($lead->budget_wedding_planner),
            ],
            [
                'title' => 'Optional extra services',
                'description' => 'Additional services available when required by the final event scope.',
                'rows' => $this->normalizedRows($lead->budget_wedding_planner_extra_services),
            ],
            [
                'title' => 'Special packages',
                'description' => 'Custom packages or special arrangements prepared for this client.',
                'rows' => $this->normalizedRows($lead->budget_wedding_planner_special_packages),
            ],
        ];
    }

    protected function normalizedRows(mixed $rows, bool $excludeWeddingPlanner = false): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function (array $row): array {
                $label = trim((string) ($row['label'] ?? ''));
                $notes = trim((string) ($row['notes'] ?? ''));

                return [
                    'label' => $label,
                    'notes' => $notes,
                    'amount' => $this->numericAmount($row['amount'] ?? null),
                ];
            })
            ->when($excludeWeddingPlanner, fn ($rows) => $rows->reject(fn (array $row): bool => $this->isWeddingPlannerRow($row)))
            ->filter(fn (array $row): bool => $row['label'] !== '' || $row['notes'] !== '' || $row['amount'] > 0)
            ->values()
            ->all();
    }

    protected function isWeddingPlannerRow(array $row): bool
    {
        $label = mb_strtolower(trim((string) ($row['label'] ?? '')));

        return in_array($label, ['wedding planner', 'wedding planning'], true);
    }

    protected function grandTotal(Lead $lead): float
    {
        return collect($this->sections($lead))
            ->sum(fn (array $section): float => $this->sectionTotal($section['rows']));
    }

    protected function sectionTotal(array $rows): float
    {
        return collect($rows)->sum(fn (array $row): float => (float) $row['amount']);
    }

    protected function numericAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    protected function money(mixed $value): string
    {
        $amount = $this->numericAmount($value);

        if ($amount <= 0) {
            return '-';
        }

        return 'EUR '.number_format($amount, 2, ',', '.');
    }

    protected function filename(Lead $lead): string
    {
        $name = Str::slug($lead->couple_name ?: 'budget');

        return sprintf('%s-fairytale-italy-weddings-budget.pdf', $name);
    }
}
