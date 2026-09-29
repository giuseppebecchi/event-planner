<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

class LeadStats extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Stats';

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'leads/stats';

    protected string $view = 'filament.pages.lead-stats';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $selectedYear = 'all';

    public static function canAccess(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->selectedYear = (string) ($this->getAvailableYears()[0] ?? now()->year);
    }

    public function showAllYears(): void
    {
        $this->selectedYear = 'all';
    }

    public function getAvailableYears(): array
    {
        return Lead::query()
            ->get(['requested_at', 'created_at'])
            ->map(fn (Lead $lead): int => $this->inquiryDate($lead)->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    public function getAnalytics(): array
    {
        $leads = Lead::query()
            ->with(['eventType', 'project', 'followUps'])
            ->get()
            ->when($this->selectedYear !== 'all', fn (Collection $items): Collection => $items
                ->filter(fn (Lead $lead): bool => $this->inquiryDate($lead)->year === (int) $this->selectedYear))
            ->values();

        $total = $leads->count();
        $qualified = $leads->where('evaluation_outcome', 'yes')->count();
        $proposed = $leads->filter(fn (Lead $lead): bool => $this->hasProposal($lead))->count();
        $converted = $leads->filter(fn (Lead $lead): bool => $this->isConverted($lead))->count();
        $lost = $leads->whereIn('status', ['rejected', 'lost'])->count();
        $completedQuestionnaires = $leads->whereNotNull('form_completed_at')->count();
        $sentQuestionnaires = $leads->whereNotNull('form_sent_at')->count();
        $budgets = $leads->pluck('budget_amount')->filter(fn ($value): bool => $value !== null && (float) $value > 0)->map(fn ($value): float => (float) $value);
        $quotedFees = $leads->sum(fn (Lead $lead): float => $this->planningFee($lead));
        $wonFees = $leads->filter(fn (Lead $lead): bool => $this->isConverted($lead))->sum(fn (Lead $lead): float => $this->planningFee($lead));
        $salesCycles = $leads
            ->filter(fn (Lead $lead): bool => $this->isConverted($lead))
            ->map(function (Lead $lead): ?int {
                $end = $lead->contract_received_at ?: $lead->project?->created_at;

                return $end ? (int) round($this->inquiryDate($lead)->diffInDays($end)) : null;
            })
            ->filter(fn ($days): bool => $days !== null);

        return [
            'period_label' => $this->selectedYear === 'all' ? 'All years' : $this->selectedYear,
            'total' => $total,
            'qualified' => $qualified,
            'proposed' => $proposed,
            'converted' => $converted,
            'lost' => $lost,
            'conversion_rate' => $this->percentage($converted, $total),
            'qualification_rate' => $this->percentage($qualified, $total),
            'proposal_rate' => $this->percentage($proposed, $total),
            'proposal_win_rate' => $this->percentage($converted, $proposed),
            'lost_rate' => $this->percentage($lost, $total),
            'questionnaire_rate' => $this->percentage($completedQuestionnaires, $sentQuestionnaires),
            'questionnaires_completed' => $completedQuestionnaires,
            'questionnaires_sent' => $sentQuestionnaires,
            'budget_total' => (float) $budgets->sum(),
            'budget_average' => $budgets->isNotEmpty() ? (float) $budgets->average() : 0,
            'budget_coverage' => $this->percentage($budgets->count(), $total),
            'quoted_fees' => (float) $quotedFees,
            'won_fees' => (float) $wonFees,
            'average_sales_cycle' => $salesCycles->isNotEmpty() ? (int) round($salesCycles->average()) : null,
            'monthly' => collect(range(1, 12))->map(function (int $month) use ($leads): array {
                $monthLeads = $leads->filter(fn (Lead $lead): bool => $this->inquiryDate($lead)->month === $month);

                return [
                    'label' => Carbon::create(null, $month, 1)->format('M'),
                    'leads' => $monthLeads->count(),
                    'converted' => $monthLeads->filter(fn (Lead $lead): bool => $this->isConverted($lead))->count(),
                ];
            })->all(),
            'funnel' => [
                ['label' => 'Inquiries', 'value' => $total, 'rate' => 100],
                ['label' => 'Qualified', 'value' => $qualified, 'rate' => $this->percentage($qualified, $total)],
                ['label' => 'Proposal sent', 'value' => $proposed, 'rate' => $this->percentage($proposed, $total)],
                ['label' => 'Converted', 'value' => $converted, 'rate' => $this->percentage($converted, $total)],
            ],
            'sources' => $this->sourcePerformance($leads),
            'statuses' => $this->distribution($leads, 'status', Lead::STATUS_OPTIONS),
            'event_types' => $this->relationDistribution($leads, fn (Lead $lead): ?string => $lead->eventType?->name, 8),
            'regions' => $this->relationDistribution($leads, fn (Lead $lead): ?string => $lead->desired_region, 8),
            'origins' => $this->relationDistribution($leads, fn (Lead $lead): ?string => $lead->country ?: $lead->nationality, 8),
            'ceremonies' => $this->distribution($leads, 'ceremony_type', Lead::CEREMONY_TYPE_OPTIONS),
            'venue_requests' => $this->distribution($leads, 'location_request_type', Lead::LOCATION_REQUEST_TYPE_OPTIONS),
            'budget_bands' => $this->bandDistribution($leads, 'budget_amount', [
                ['label' => '< EUR 50k', 'min' => 0, 'max' => 50000],
                ['label' => 'EUR 50–100k', 'min' => 50000, 'max' => 100000],
                ['label' => 'EUR 100–200k', 'min' => 100000, 'max' => 200000],
                ['label' => 'EUR 200k+', 'min' => 200000, 'max' => null],
            ]),
            'guest_bands' => $this->bandDistribution($leads, 'estimated_guest_count', [
                ['label' => 'Up to 30', 'min' => 0, 'max' => 31],
                ['label' => '31–60', 'min' => 31, 'max' => 61],
                ['label' => '61–100', 'min' => 61, 'max' => 101],
                ['label' => '101–150', 'min' => 101, 'max' => 151],
                ['label' => '151+', 'min' => 151, 'max' => null],
            ]),
            'priorities' => $this->questionnaireDistribution($leads, 'priority_services', 8),
            'venue_types' => $this->questionnaireDistribution($leads, 'venue_types', 8),
            'discovery_sources' => $this->questionnaireDistribution($leads, 'discovery_source', 8),
            'follow_up_completion_rate' => $this->followUpCompletionRate($leads),
        ];
    }

    protected function inquiryDate(Lead $lead): Carbon
    {
        return Carbon::parse($lead->requested_at ?: $lead->created_at);
    }

    protected function hasProposal(Lead $lead): bool
    {
        return filled($lead->proposal_sent_at)
            || in_array($lead->status, ['proposal_sent', 'confirmed', 'transferred'], true)
            || $this->isConverted($lead);
    }

    protected function isConverted(Lead $lead): bool
    {
        return $lead->project !== null
            || filled($lead->contract_received_at)
            || in_array($lead->status, ['confirmed', 'transferred'], true);
    }

    protected function planningFee(Lead $lead): float
    {
        $base = collect($lead->budget_wedding_planner ?? [])->sum(fn (array $row): float => $this->rowAmount($row));
        $extras = collect([
            ...($lead->budget_wedding_planner_extra_services ?? []),
            ...($lead->budget_wedding_planner_special_packages ?? []),
        ])->filter(fn (array $row): bool => (bool) ($row['add_to_budget'] ?? false))
            ->sum(fn (array $row): float => $this->rowAmount($row));

        return round($base + $extras, 2);
    }

    protected function rowAmount(array $row): float
    {
        return (float) str_replace(',', '.', (string) ($row['amount'] ?? 0));
    }

    protected function percentage(int|float $part, int|float $whole): float
    {
        return $whole > 0 ? round(($part / $whole) * 100, 1) : 0;
    }

    protected function sourcePerformance(Collection $leads): array
    {
        return $leads
            ->groupBy(fn (Lead $lead): string => $lead->source ?: 'unknown')
            ->map(function (Collection $items, string $source): array {
                $converted = $items->filter(fn (Lead $lead): bool => $this->isConverted($lead))->count();

                return [
                    'label' => Lead::SOURCE_OPTIONS[$source] ?? ucfirst(str_replace('_', ' ', $source)),
                    'value' => $items->count(),
                    'converted' => $converted,
                    'rate' => $this->percentage($converted, $items->count()),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    protected function distribution(Collection $leads, string $field, array $labels): array
    {
        return $this->relationDistribution(
            $leads,
            fn (Lead $lead): ?string => filled($lead->{$field}) ? ($labels[$lead->{$field}] ?? $lead->{$field}) : null,
        );
    }

    protected function relationDistribution(Collection $leads, callable $resolver, int $limit = 12): array
    {
        $values = $leads
            ->map(fn (Lead $lead): ?string => $this->normalizeLabel($resolver($lead)))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take($limit);
        $total = $values->sum();

        return $values
            ->map(fn (int $count, string $label): array => [
                'label' => $label,
                'value' => $count,
                'rate' => $this->percentage($count, $total),
            ])
            ->values()
            ->all();
    }

    protected function bandDistribution(Collection $leads, string $field, array $bands): array
    {
        $known = $leads->filter(fn (Lead $lead): bool => $lead->{$field} !== null && (float) $lead->{$field} > 0);

        return collect($bands)->map(function (array $band) use ($known, $field): array {
            $items = $known->filter(function (Lead $lead) use ($band, $field): bool {
                $value = (float) $lead->{$field};

                return $value >= $band['min'] && ($band['max'] === null || $value < $band['max']);
            });
            $converted = $items->filter(fn (Lead $lead): bool => $this->isConverted($lead))->count();

            return [
                'label' => $band['label'],
                'value' => $items->count(),
                'rate' => $this->percentage($items->count(), $known->count()),
                'conversion_rate' => $this->percentage($converted, $items->count()),
            ];
        })->all();
    }

    protected function questionnaireDistribution(Collection $leads, string $key, int $limit): array
    {
        $values = $leads
            ->flatMap(function (Lead $lead) use ($key): array {
                $value = data_get($lead->form_payload, $key);

                if (blank($value)) {
                    return [];
                }

                return is_array($value) ? $value : [$value];
            })
            ->map(fn ($value): ?string => $this->normalizeLabel($value))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take($limit);
        $total = $values->sum();

        return $values->map(fn (int $count, string $label): array => [
            'label' => $label,
            'value' => $count,
            'rate' => $this->percentage($count, $total),
        ])->values()->all();
    }

    protected function followUpCompletionRate(Collection $leads): float
    {
        $followUps = $leads->flatMap(fn (Lead $lead): Collection => $lead->followUps);

        return $this->percentage($followUps->where('status', 'completed')->count(), $followUps->count());
    }

    protected function normalizeLabel(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
