<?php

namespace Dartmoon\LaravelLocalizedRoutes\LocaleProviders\Contracts;

interface LocaleProviderContract
{
    public function isDefaultLocale(string $locale): bool;

    public function getDefaultLocale(): string|null;

    public function getAvailableLocales(): array;

    public function getLocaleName(string $locale, string $default = null): string;
}