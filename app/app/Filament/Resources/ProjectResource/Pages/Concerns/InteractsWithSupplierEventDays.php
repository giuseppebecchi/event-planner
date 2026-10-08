<?php

namespace App\Filament\Resources\ProjectResource\Pages\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

trait InteractsWithSupplierEventDays
{
    public function getAvailableEventDays(): array
    {
        $project = $this->getRecord();
        $start = $project->event_start_date ?: $project->event_date;
        $end = $project->event_end_date ?: $start;

        if (! $start) {
            return [];
        }

        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $days = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $days[] = [
                'date' => $day->toDateString(),
                'label' => $day->translatedFormat('D d M Y'),
            ];
        }

        return $days;
    }

    protected function eventDayFormData(?array $allocations): array
    {
        $allocationsByDate = collect($allocations ?? [])->keyBy('date');

        return collect($this->getAvailableEventDays())
            ->map(function (array $day) use ($allocationsByDate): array {
                $allocation = $allocationsByDate->get($day['date']);

                return [
                    'date' => $day['date'],
                    'label' => $day['label'],
                    'selected' => $allocation !== null,
                    'amount' => $allocation !== null && array_key_exists('amount', $allocation) && $allocation['amount'] !== null
                        ? (string) $allocation['amount']
                        : '',
                ];
            })
            ->values()
            ->all();
    }

    protected function validatedEventDayAllocations(array $rows, mixed $quoteAmount, string $errorKey): array
    {
        $availableDates = collect($this->getAvailableEventDays())->pluck('date')->all();
        $selected = collect($rows)
            ->filter(fn (array $row): bool => (bool) ($row['selected'] ?? false))
            ->values();

        foreach ($selected as $row) {
            if (! in_array((string) ($row['date'] ?? ''), $availableDates, true)) {
                throw ValidationException::withMessages([$errorKey => 'One of the selected days is outside the event dates.']);
            }
        }

        $usesAllocation = $selected->contains(fn (array $row): bool => filled($row['amount'] ?? null));

        if ($usesAllocation) {
            if ($quoteAmount === null || $quoteAmount === '') {
                throw ValidationException::withMessages([$errorKey => 'Enter the quote total before allocating it across event days.']);
            }

            if ($selected->contains(fn (array $row): bool => ! is_numeric($row['amount'] ?? null) || (float) $row['amount'] < 0)) {
                throw ValidationException::withMessages([$errorKey => 'Enter an amount for every selected day.']);
            }

            $allocatedTotal = round($selected->sum(fn (array $row): float => (float) $row['amount']), 2);
            $quoteTotal = round((float) $quoteAmount, 2);

            if (abs($allocatedTotal - $quoteTotal) > 0.009) {
                throw ValidationException::withMessages([
                    $errorKey => 'The daily allocation must equal the quote total (EUR '.number_format($quoteTotal, 2, '.', ',').').',
                ]);
            }
        }

        return $selected
            ->map(fn (array $row): array => [
                'date' => (string) $row['date'],
                'amount' => $usesAllocation ? round((float) $row['amount'], 2) : null,
            ])
            ->all();
    }
}
