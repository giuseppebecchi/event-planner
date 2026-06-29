<?php

namespace App\Filament\Resources\ConfigResource\Pages;

use App\Filament\Resources\ConfigResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditConfig extends EditRecord
{
    protected static string $resource = ConfigResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
