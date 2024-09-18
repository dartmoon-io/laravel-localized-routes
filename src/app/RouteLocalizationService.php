<?php

namespace Dartmoon\LaravelLocalizedRoutes;

use Carbon\Carbon;
use Dartmoon\LaravelLocalizedRoutes\LocaleProviders\Contracts\LocaleProviderContract;
use Illuminate\Support\Facades\URL;

class RouteLocalizationService
{
    public function __construct(
        protected LocaleProviderContract $provider
    ) {
        //
    }

    public function localizeRoute(string $name, mixed $parameters = [], string $locale = null, bool $absolute = true): string
    {
        if (app()->getLocale() != $locale) {
            $name = $locale . '.' . $name;
        }

        return route($name, $parameters, $absolute);
    }

    public function localizeUrl(string $url, string $locale = null): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = ltrim($path, '/');
        $pathExploded = explode('/', $path);

        // Remove the locale if exists
        if (count($pathExploded) > 1 && in_array($pathExploded[0], available_locales())) {
            array_shift($pathExploded);
        }

        // Let's recompose the path and return it
        $path = implode('/', $pathExploded);
        return URL::to(($this->isDefaultLocale($locale) ? '' : $locale) . '/' . $path);
    }

    public function isCurrentLocaleDefault(): bool
    {
        return $this->isDefaultLocale(app()->getLocale());
    }

    public function isDefaultLocale(string $locale): bool
    {
        return $this->provider->isDefaultLocale($locale);
    }

    protected function isAValidLocale(string $locale): bool
    {
        return in_array($locale, $this->getAvailableLocales());
    }

    protected function getDefaultLocale(): string|null
    {
        return $this->provider->getDefaultLocale();
    }

    public function getAvailableLocales(): array
    {
        return $this->provider->getAvailableLocales();
    }

    public function getLocaleName(string $locale, string $default = null): string
    {
        return $this->provider->getLocaleName($locale, $default);
    }

    public function setRequestLocale(): void
    {
        $locale = $this->getLocaleFromRequest();
        $locale = $this->isAValidLocale($locale) ? $locale : $this->getDefaultLocale();

        // Localize the app
        app()->setLocale($locale);

        // Localize carbon
        Carbon::setLocale($locale);

        // Set the default parameter to be included when creating urls
        URL::defaults(['locale' => $locale]);
    }

    protected function getLocaleFromRequest(): string|null
    {
        $request = app()->make('request');
        $segments = $request->segments();
        return $segments[0] ?? null;
    }
}