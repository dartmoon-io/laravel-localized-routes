<?php

namespace Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders;

use Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\Contracts\LocaleProviderContract;

class DefaultLocaleProvider implements LocaleProviderContract
{
    public function getDefaultLocale(): string|null
    {
        return config('locales.default') ?? null;
    }

    public function getAvailableLocales(bool $caching = false): array
    {
        return array_keys(config('locales.available') ?? []);
    }

    public function getLocaleName(string $locale, string $default = null): string
    {
        return config('locales.available')[$locale] ?? $default ?? $locale;
    }
}