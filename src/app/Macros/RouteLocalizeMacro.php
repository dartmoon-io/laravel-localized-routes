<?php

namespace Dartmoon\LaravelLocalizedRoutes\App\Macros;

use Dartmoon\LaravelLocalizedRoutes\App\Macros\Contracts\MacroContract;
use Illuminate\Support\Facades\Route;

class RouteLocalizeMacro implements MacroContract
{
    protected static $methods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'any', 'match'];

    public static function register(): void
    {
        self::registerRouteLocalizeMacro();

        collect(self::$methods)->each(fn ($method) => self::registerRouteMethodLocalizeMacro($method));
    }

    protected static function registerRouteLocalizeMacro(): void
    {
        Route::macro('localize', function ($callback) {

            // Add compatibility with route helper
            Route::when(!is_current_locale_default(), fn () => Route::group(['prefix' => app()->getLocale(), 'locale' => app()->getLocale()], $callback));
            Route::when(is_current_locale_default(), fn () => Route::group(['locale' => app()->getLocale()], $callback));

            collect(available_locales())
                ->filter(fn ($locale) => $locale != app()->getLocale())
                ->each(function ($locale) use ($callback) {
                    Route::when(is_default_locale($locale), fn () => Route::group(['as' => "{$locale}.", 'locale' => $locale], $callback));
                    Route::when(!is_default_locale($locale), fn () => Route::group(['prefix' => $locale, 'as' => "{$locale}.", 'locale' => $locale], $callback));
                });

            return $this;
        });
    }

    protected static function registerRouteMethodLocalizeMacro(string $method): void
    {
        Route::macro($method . 'Localized', function ($uri, $action = null) use ($method) {
            // Let's obtain the locale
            $locale = null;
            foreach ($this->groupStack as $group) {
                $locale = $group['locale'] ?? $locale;
            }

            $translatedUri = trans("routes.$uri", [], $locale);

            // if we could find a translation for the uri, then we use
            // the original uri
            if ($translatedUri == "routes.$uri") {
                $translatedUri = $uri;
            }

            return Route::$method($translatedUri, $action);
        });
    }
}
