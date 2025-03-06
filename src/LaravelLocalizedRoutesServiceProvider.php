<?php

namespace Dartmoon\LaravelLocalizedRoutes;

use Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\Contracts\LocaleProviderContract;
use Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\DefaultLocaleProvider;
use Dartmoon\LaravelLocalizedRoutes\App\Mixins\RouteLocalizeMixin;
use Dartmoon\LaravelLocalizedRoutes\App\RouteLocalizationService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelLocalizedRoutesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerServices();
        $this->registerMixins();

        $this->app->booted(function () {
            $routeLocalizationService = app(RouteLocalizationService::class);
            $routeLocalizationService->setLocaleFromRequest();

            Route::registerLocalizedRoutesForLocale(app()->getLocale());
        });
    }

    public function boot(): void
    {
        $this->loadConfigs();
    }

    protected function loadConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/locales.php', 'locales');
        $this->mergeConfigFrom(__DIR__ . '/config/localized-routes.php', 'localized-routes');
    }

    protected function registerServices(): void
    {
        $this->app->bind(LocaleProviderContract::class, DefaultLocaleProvider::class);
    }

    protected function registerMixins(): void
    {
        Route::mixin(new RouteLocalizeMixin());
    }

    protected function setLocaleFromRequest(): void
    {
        $this->app->get(RouteLocalizationService::class)->setLocaleFromRequest();
    }
}
