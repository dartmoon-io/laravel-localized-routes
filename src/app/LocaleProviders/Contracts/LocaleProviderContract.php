<?php

namespace Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\Contracts;

interface LocaleProviderContract
{
    public function getDefaultLocale(): string|null;

    public function getAvailableLocales(bool $caching = false): array;

    public function getLocaleName(string $locale, ?string $default = null): string;
}