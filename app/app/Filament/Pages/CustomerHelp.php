<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class CustomerHelp extends Page
{
    protected static bool $shouldRegisterNavigation = true;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Help';

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'help';

    protected string $view = 'filament.pages.customer-help';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function canAccess(): bool
    {
        return auth()->user()?->isCustomer() ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Help';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }
}
