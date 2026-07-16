<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class CustomerWelcome extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'customer-welcome';

    protected string $view = 'filament.pages.customer-welcome';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function canAccess(): bool
    {
        return auth()->user()?->isCustomer() ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Welcome';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function mount(): void
    {
        $this->markWelcomeAsSeen();
    }

    public function continueToPortal()
    {
        $this->markWelcomeAsSeen();

        return redirect($this->customerHomeUrl());
    }

    protected function markWelcomeAsSeen(): void
    {
        $user = auth()->user();

        if ($user && blank($user->customer_portal_welcomed_at)) {
            $user->forceFill([
                'customer_portal_welcomed_at' => now(),
            ])->save();
        }
    }

    protected function customerHomeUrl(): string
    {
        $user = auth()->user();

        if (! $user?->isCustomer()) {
            return url('/admin');
        }

        $projects = $user->projects()->orderBy('event_date')->orderBy('name')->get();

        if ($projects->count() === 1) {
            return ProjectResource::getUrl('view', ['record' => $projects->first()]);
        }

        return ProjectResource::getUrl();
    }
}
