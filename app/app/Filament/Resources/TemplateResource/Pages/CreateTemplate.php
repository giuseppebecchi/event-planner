<?php

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
