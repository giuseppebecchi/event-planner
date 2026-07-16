<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\CustomerEventDashboard;
use App\Filament\Pages\CustomerHelp;
use App\Filament\Pages\CustomerWelcome;
use App\Filament\Pages\EditProfile;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\ProjectResource;
use App\Http\Middleware\RedirectCustomerToWelcome;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\View\PanelsRenderHook;
use Filament\Auth\Pages\Login;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditProfile::class, isSimple: false)
            ->topNavigation()
            ->subNavigationPosition(SubNavigationPosition::Top)
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->homeUrl(fn (): string => $this->homeUrl())
            ->brandLogo(asset('images/logo-negative-heart-gold-ai.png'))
            ->darkModeBrandLogo(asset('images/logo-negative-heart-gold-ai.png'))
            ->brandLogoHeight('2.85rem')
            ->colors([
                'primary' => '#7A8F7B',
                'gray' => Color::Stone,
                'info' => '#2E4A62',
                'success' => '#7A8F7B',
                'warning' => '#C9A96A',
                'danger' => '#E3B7B2',
            ])
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn (): string => view('filament.auth.login-branding')->render(),
                scopes: Login::class,
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.auth.login-credit')->render(),
                scopes: Login::class,
            )
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                CustomerEventDashboard::class,
                CustomerHelp::class,
                CustomerWelcome::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                RedirectCustomerToWelcome::class,
            ]);
    }

    protected function homeUrl(): string
    {
        $user = auth()->user();

        if (! $user?->isCustomer()) {
            return LeadResource::getUrl(panel: 'admin');
        }

        if (blank($user->customer_portal_welcomed_at)) {
            return CustomerWelcome::getUrl(panel: 'admin');
        }

        $projects = $user->projects()->orderBy('event_date')->orderBy('name')->get();

        if ($projects->count() === 1) {
            return ProjectResource::getUrl('view', ['record' => $projects->first()], panel: 'admin');
        }

        return ProjectResource::getUrl(panel: 'admin');
    }
}
