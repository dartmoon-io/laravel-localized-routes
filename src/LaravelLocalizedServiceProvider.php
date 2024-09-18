<?php

namespace Dartmoon\LaravelLocalizedRoutes;

use Carbon\Carbon;
use Dartmoon\LaravelLocalizedRoutes\App\Macros\RouteLocalizeMacro;
use Dartmoon\LaravelLocalizedRoutes\LocaleProviders\Contracts\LocaleProviderContract;
use Dartmoon\LaravelLocalizedRoutes\LocaleProviders\DefaultLocaleProvider;
use Illuminate\Support\Facades\URL;
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

        $this->setRequestLocale();
    }

    protected function loadConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/locales.php', 'locales');
    }

    protected function registerServices(): void
    {
        $this->app->bind(LocaleProviderContract::class, DefaultLocaleProvider::class);
        $this->app->singleton(Localization::class);
    }

    protected function registerMacros(): void
    {
        RouteLocalizeMacro::register();
    }

    protected function setRequestLocale(): void
    {
        $this->app->get(RouteLocalizationService::class)->setRequestLocale();
    }
}
