<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use App\Models\Team;
use Filament\PanelProvider;
use App\Filament\Auth\Login;

use App\Filament\Sys\Widgets;
use Filament\Facades\Filament;

use Filament\Support\Colors\Color;
use App\Filament\Sys\Pages\Dashboard;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Sys\Pages\Tenancy\RegisterTeam;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Sys\Pages\Tenancy\EditTeamProfile;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class SysPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sys')
            ->path('sys')
            ->login(Login::class)
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->tenantRegistration(RegisterTeam::class)
            ->tenantProfile(EditTeamProfile::class)
            ->tenant(Team::class, slugAttribute: 'slug')
            ->viteTheme('resources/css/filament/sys/theme.css')
            ->tenantMenu(isset(request()->segments()[2]) && request()->segments()[2] == 'choose-company' ? false : true)
            ->navigation(isset(request()->segments()[2]) && request()->segments()[2] == 'choose-company' ? false : true)
            ->databaseNotifications()
            ->favicon(asset('assets/logo.png'))
            ->brandName('Billing System')
            ->brandLogo(asset('assets/logo.png'))
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Billing'),
                NavigationGroup::make()
                    ->label('Resources'),
                NavigationGroup::make()
                    ->label('Setting'),
            ])
            ->navigationItems([
                NavigationItem::make('Organization')
                ->label(__('Organization'))
                ->isActiveWhen(fn (): bool => url()->current() == Filament::getUrl().'/profile')
                ->url(fn () => Filament::getUrl().'/profile')
                ->icon('heroicon-o-presentation-chart-line')
                ->group('Setting')
                ->sort(1),
            ])
            ->colors([
                'primary' => '#199608',
            ])
            ->discoverResources(in: app_path('Filament/Sys/Resources'), for: 'App\\Filament\\Sys\\Resources')
            ->discoverPages(in: app_path('Filament/Sys/Pages'), for: 'App\\Filament\\Sys\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Sys/Widgets'), for: 'App\\Filament\\Sys\\Widgets')
            ->widgets([
                Widgets\PaymentChart::class,
                Widgets\StatsOverview::class,
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
            ]);
    }
}
