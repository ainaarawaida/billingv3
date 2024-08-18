<?php

namespace App\Providers\Filament;

use App\Filament\Home\Pages\Home;
use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class HomePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('home')
            ->path('/')
            ->colors([
                'primary' => Color::Amber,
            ]) 
            ->sidebarCollapsibleOnDesktop(true)
            ->brandLogo(asset('assets/logo.png'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('assets/logo.png'))
            ->topNavigation()
            ->spa()
            ->viteTheme('resources/css/filament/home/theme.css')
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER ,
                function(): string {
                    // dd(auth()->user()->roles);
                    if(auth()->check()) {

                        $stringhtml = '' ;
                        if(auth()->user()->hasRole('admin')){
                            $stringhtml .= '<a wire:navigate href="'.url('/admin/login').'" class="fi-topbar-item-button flex items-center justify-center gap-x-2 rounded-lg px-3 py-2 outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 bg-gray-50 dark:bg-white/5">
                   
                        <span class="fi-topbar-item-label text-sm font-medium text-primary-600 dark:text-primary-400">
                            Admin
                        </span></a>' ;
                        }

                        if(auth()->user()->hasRole('customer')){
                            $stringhtml .= '<a wire:navigate href="'.url('/sys/login').'" class="fi-topbar-item-button flex items-center justify-center gap-x-2 rounded-lg px-3 py-2 outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 bg-gray-50 dark:bg-white/5">
                   
                        <span class="fi-topbar-item-label text-sm font-medium text-primary-600 dark:text-primary-400">
                            Dashboard
                        </span></a>' ;
                        }

                    

                        return $stringhtml;
                    }
                    return '<a wire:navigate href="'.url('/sys/login').'" class="fi-topbar-item-button flex items-center justify-center gap-x-2 rounded-lg px-3 py-2 outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 bg-gray-50 dark:bg-white/5">
                   
                        <span class="fi-topbar-item-label text-sm font-medium text-primary-600 dark:text-primary-400">
                            Login
                        </span></a>
                        ' ;
                }

            )
            ->discoverResources(in: app_path('Filament/Home/Resources'), for: 'App\\Filament\\Home\\Resources')
            ->discoverPages(in: app_path('Filament/Home/Pages'), for: 'App\\Filament\\Home\\Pages')
            ->pages([
                Home::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Home/Widgets'), for: 'App\\Filament\\Home\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
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
                // Authenticate::class,
            ]);
    }
}
