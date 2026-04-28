<?php

namespace App\Filament\Resources\ProjectResource\Pages\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

trait InteractsWithProjectDateEditor
{
    public bool $showProjectDateEditor = false;

    public array $projectDateForm = [
        'is_multi_day' => false,
        'single_date' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    public function openProjectDateEditor(): void
    {
        $record = $this->getRecord();
        $startDate = $record->event_start_date;
        $endDate = $record->event_end_date;
        $isMultiDay = $startDate && $endDate && ! $startDate->isSameDay($endDate);

        $this->projectDateForm = [
            'is_multi_day' => $isMultiDay,
            'single_date' => $startDate?->format('Y-m-d') ?? '',
            'start_date' => $startDate?->format('Y-m-d') ?? '',
            'end_date' => $endDate?->format('Y-m-d') ?? ($startDate?->format('Y-m-d') ?? ''),
        ];

        $this->showProjectDateEditor = true;
    }

    public function cancelProjectDateEditor(): void
    {
        $this->showProjectDateEditor = false;
    }

    public function saveProjectDateEditor(): void
    {
        $data = validator($this->projectDateForm, [
            'is_multi_day' => ['required', 'boolean'],
            'single_date' => ['nullable', 'date', 'required_if:is_multi_day,false'],
            'start_date' => ['nullable', 'date', 'required_if:is_multi_day,true'],
            'end_date' => ['nullable', 'date', 'required_if:is_multi_day,true', 'after_or_equal:start_date'],
        ])->validate();

        $record = $this->getRecord();

        if ($data['is_multi_day']) {
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $endDate = Carbon::parse($data['end_date'])->startOfDay();
        } else {
            $singleDate = Carbon::parse($data['single_date'])->startOfDay();
            $startDate = $singleDate;
            $endDate = $singleDate;
        }

        $record->forceFill([
            'event_start_date' => $startDate,
            'event_end_date' => $endDate,
        ])->save();

        $record->refresh();
        $this->showProjectDateEditor = false;

        Notification::make()
            ->title('Event date updated')
            ->success()
            ->send();
    }
}
