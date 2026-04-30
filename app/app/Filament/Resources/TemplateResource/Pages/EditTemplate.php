<?php

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditTemplate extends EditRecord
{
    protected static string $resource = TemplateResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
