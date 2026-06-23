<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Models\ProjectChecklistOption;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class ViewProjectRecap extends ViewProjectTimeline
{
    protected string $view = 'filament.resources.project-resource.pages.view-project-recap';

    protected static ?string $breadcrumb = 'Recap';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getRecapChecklistItems(): Collection
    {
        return $this->getRecord()
            ->projectChecklistOptions()
            ->with(['supplier', 'checklist.category'])
            ->where('enabled', true)
            ->where('insert_into_recap', true)
            ->orderBy('due_date')
            ->orderBy('order')
            ->get()
            ->filter(fn (ProjectChecklistOption $item): bool => filled($item->response) || filled($item->details) || filled($item->title))
            ->values();
    }
}
