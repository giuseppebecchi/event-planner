<?php

namespace App\Filament\Resources\ChecklistResource\Pages;

use App\Filament\Resources\ChecklistResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditChecklist extends EditRecord
{
    protected static string $resource = ChecklistResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
