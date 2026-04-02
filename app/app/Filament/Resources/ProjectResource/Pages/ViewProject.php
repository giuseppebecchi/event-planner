<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected static ?string $breadcrumb = 'Overview';

    public function getTitle(): string | Htmlable
    {
        return (string) $this->getRecordTitle();
    }

    public function getSubheading(): string | Htmlable | null
    {
        $record = $this->getRecord();

        $location = collect([$record->locality, $record->region])
            ->filter()
            ->implode(', ');

        $date = $record->event_start_date?->format('d/m/Y');

        return collect([$location, $date, Project::STATUS_OPTIONS[$record->status] ?? null])
            ->filter()
            ->implode('  |  ');
    }
}
