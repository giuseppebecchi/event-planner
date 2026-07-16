<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Pages\Page;

class CustomerEventDashboard extends Page
{
    protected static bool $shouldRegisterNavigation = true;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'customer-dashboard';

    protected string $view = 'filament.pages.customer-event-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isCustomer() && $user->customerProjectsCount() === 1);
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getNavigationUrl(): string
    {
        return static::customerProjectDashboardUrl() ?? static::getUrl();
    }

    public function mount()
    {
        return redirect(static::customerProjectDashboardUrl() ?? ProjectResource::getUrl());
    }

    protected static function customerProjectDashboardUrl(): ?string
    {
        $project = auth()->user()?->projects()->orderBy('event_date')->orderBy('name')->first();

        return $project ? ProjectResource::getUrl('view', ['record' => $project]) : null;
    }
}
