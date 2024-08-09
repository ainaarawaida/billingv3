<?php

namespace App\Providers;

use Livewire\Livewire;
use App\Filament\Home\Pages\Home;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->singleton(
            LoginResponse::class,
            \App\Http\Responses\Auth\LoginResponse::class
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            function(){
                return "<script data-navigate-once>
                document.addEventListener('alpine:init', function() {
                    // console.log(window.Alpine.store('sidebar').close());
                    window.Alpine.store('sidebar').close();
                   
                  });
                  </script>";
             
            },
            scopes: [
                Home::class,
            ]
        );

        //
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            // fn () => view('customFooter'),
            fn () => Blade::render('@livewire(\'footer\')')
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        If (env('APP_ENV') !== 'local') {
            // URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', true);
        }

        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/livewire/livewire.js', $handle)->middleware('web');
        });
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware('web')->name('custom-update');
        });

    }
}
