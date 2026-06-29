<?php

namespace App\Filament\Resources\ConfigResource\Pages;

use App\Filament\Resources\ConfigResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateConfig extends CreateRecord
{
    protected static string $resource = ConfigResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
