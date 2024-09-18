<?php

namespace Dartmoon\LaravelLocalizedRoutes\LocaleProviders;

use Dartmoon\LaravelLocalizedRoutes\LocaleProviders\Contracts\LocaleProviderContract;

class DefaultLocaleProvider implements LocaleProviderContract
{
    public function isDefaultLocale($locale): bool
    {
        return $locale === $this->getDefaultLocale();
    }

    public function getDefaultLocale(): string|null
    {
        return config('locales.default') ?? null;
    }

    public function getAvailableLocales(): array
    {
        return array_keys(config('locales.available') ?? []);
    }

    public function getLocaleName(string $locale, string $default = null): string
    {
        return config('locales.available')[$locale] ?? $default ?? $locale;
    }
}