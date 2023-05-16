<?php

namespace Dartmoon\LaravelLocalized;

use Carbon\Carbon;
use Dartmoon\LaravelLocalized\App\Macros\RouteLocalizeMacro;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class LaravelLocalizedServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerMacros();
    }

    public function boot()
    {
        $this->setLocale();

        $this->loadConfigs();
    }

    public function loadConfigs()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/locale.php', 'locale');
    }

    public function registerMacros()
    {
        RouteLocalizeMacro::register();
    }

    protected function setLocale()
    {
        $locale = $this->getLocaleFromRequest();
        $locale = $this->isAValidLocale($locale) ? $locale : $this->getDefaultLocale();

        // Localize the app
        $this->app->setLocale($locale);

        // Localize carbon
        Carbon::setLocale($locale);

        // Set the default parameter to be included when creating urls
        URL::defaults(['locale' => $locale]);
    }

    protected function isAValidLocale($locale)
    {
        return in_array($locale, $this->getAvailableLocales());
    }

    protected function getAvailableLocales()
    {
        return array_keys(config('locale.available'));
    }

    protected function getDefaultLocale()
    {
        return config('locale.default');
    }

    protected function getLocaleFromRequest()
    {
        $request = $this->app->make('request');
        $segments = $request->segments();
        return $segments[0] ?? null;
    }
}
