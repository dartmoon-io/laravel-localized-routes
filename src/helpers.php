<?php

use Dartmoon\LaravelLocalizedRoutes\App\RouteLocalizationService;

if (!function_exists('route_localized')) {
    function route_localized(string $name, mixed $parameters = [], string $locale = null, bool $absolute = true): string
    {
        return app(RouteLocalizationService::class)->localizeRoute($name, $parameters, $locale, $absolute);
    }
}

if (!function_exists('url_localized')) {
    function url_localized(string $url, string $locale = null): string
    {
        return app(RouteLocalizationService::class)->localizeUrl($url, $locale);
    }
}

if (!function_exists('available_locales')){
    function available_locales(): array
    {
        return app(RouteLocalizationService::class)->getAvailableLocales();
    }
}

if (!function_exists('is_default_locale')) {
    function is_default_locale(string $locale): bool
    {
        return app(RouteLocalizationService::class)->isDefaultLocale($locale);
    }
}

if (!function_exists('is_current_locale_default')) {
    function is_current_locale_default(): bool
    {
        return app(RouteLocalizationService::class)->isCurrentLocaleDefault();
    }
}

if (!function_exists('locale_name')) {
    function locale_name(string $locale, string $default = null): string
    {
        return app(RouteLocalizationService::class)->getLocaleName($locale, $default);
    }
}

if (!function_exists('default_locale')) {
    function default_locale(): string
    {
        return app(RouteLocalizationService::class)->getDefaultLocale();
    }
}

if (!function_exists('available_alternates')) {
    function available_alternates(): array
    {
        return app(RouteLocalizationService::class)->getAvailableAlternates();
    }
}

