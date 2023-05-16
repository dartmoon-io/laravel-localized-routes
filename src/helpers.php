<?php

use Illuminate\Support\Facades\URL;

if (!function_exists('route_localized')) {
    function route_localized($name, $parameters = [], $locale = null, $absolute = true)
    {
        if (app()->getLocale() != $locale) {
            $name = $locale . '.' . $name;
        }

        return route($name, $parameters, $absolute);
    }
}

if (!function_exists('url_localized')) {
    function url_localized($url, $locale = null)
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
        return URL::to((is_default_locale($locale) ? '' : $locale) . '/' . $path);
    }
}

if (!function_exists('available_locales')){
    function available_locales()
    {
        return array_keys(config('locale.available'));
    }
}

if (!function_exists('is_default_locale')) {
    function is_default_locale($locale)
    {
        return $locale === config('locale.default');
    }
}

if (!function_exists('is_current_locale_default')) {
    function is_current_locale_default()
    {
        return is_default_locale(app()->getLocale());
    }
}

if (!function_exists('locale_name')) {
    function locale_name($locale, $default = null)
    {
        return config('locale.available')[$locale] ?? $default ?? $locale;
    }
}
