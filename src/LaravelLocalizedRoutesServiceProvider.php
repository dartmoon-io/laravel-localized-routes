<?php

namespace Dartmoon\LaravelLocalizedRoutes;

use Dartmoon\LaravelLocalizedRoutes\App\Macros\RouteLocalizeMacro;
use Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\Contracts\LocaleProviderContract;
use Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\DefaultLocaleProvider;
use Dartmoon\LaravelLocalizedRoutes\App\RouteLocalizationService;
use Illuminate\Support\ServiceProvider;

class LaravelLocalizedRoutesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerServices();
        $this->registerMacros();
    }

    public function boot(): void
    {
        $this->loadConfigs();

        // If the application is server by octane,
        // then we cannot set the request locale
        // from the provider 
        if (!$this->runningInOctane()) {
            $this->setLocaleFromRequest();
        }
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

    protected function registerMacros(): void
    {
        RouteLocalizeMacro::register();
    }

    protected function setLocaleFromRequest(): void
    {
        $this->app->get(RouteLocalizationService::class)->setLocaleFromRequest();
    }

    protected function runningInOctane(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE']) && ((int)$_SERVER['LARAVEL_OCTANE'] === 1);
    }
}
