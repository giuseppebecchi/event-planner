<?php

namespace App\Filament\Resources\ChecklistResource\Pages;

use App\Filament\Resources\ChecklistResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateChecklist extends CreateRecord
{
    protected static string $resource = ChecklistResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
