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
            // Prefix the current route with the right locale
            if (config('localized-routes.prefix_default_locale')) {
                Route::group(['prefix' => app()->getLocale(), 'locale' => app()->getLocale()], $callback);
                Route::group([], fn () => Route::get('{any}', fn () => redirect(default_locale() . '/' . request()->path())))->where('any', '.*');
            } else {
                // Add compatibility with route helper
                Route::when(!is_current_locale_default(), fn () => Route::group(['prefix' => app()->getLocale(), 'locale' => app()->getLocale()], $callback));
                Route::when(is_current_locale_default(), fn () => Route::group(['locale' => app()->getLocale()], $callback)); // Do not prefix the default locale
            }

            // Register localized routes for the other available locales
            collect(available_locales())
                ->filter(fn ($locale) => $locale != app()->getLocale())
                ->each(function ($locale) use ($callback) {
                    if (config('localized-routes.prefix_default_locale')) {
                        Route::group(['prefix' => $locale, 'as' => "{$locale}.", 'locale' => $locale], $callback);
                    } else {
                        // Add compatibility with route helper
                        Route::when(!is_default_locale($locale), fn () => Route::group(['prefix' => $locale, 'as' => "{$locale}.", 'locale' => $locale], $callback));
                        Route::when(is_default_locale($locale), fn () => Route::group(['as' => "{$locale}.", 'locale' => $locale], $callback)); // Do not prefix the default locale
                    }
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

            // All the possible translations for the uri
            $transKeys = [
                "routes.$uri",
                "routes." . ltrim($uri, '/'),
                "routes." . rtrim($uri, '/'),
                "routes." . trim($uri, '/'),
                "routes./" . trim($uri, '/'),
                "routes." . trim($uri, '/') . "/",
                "routes./" . trim($uri, '/') . "/",
            ];

            $translatedUri = $uri;
            foreach ($transKeys as $transKey) {
                $tranlatedUriTemp = trans($transKey, [], $locale);

                if ($tranlatedUriTemp != $transKey) {
                    $translatedUri = $tranlatedUriTemp;
                    break;
                }
            }

            return Route::$method($translatedUri, $action);
        });
    }
}
