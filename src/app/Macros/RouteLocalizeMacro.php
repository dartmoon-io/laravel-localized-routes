<?php

namespace Dartmoon\LaravelLocalizedRoutes\App\Macros;

use Dartmoon\LaravelLocalizedRoutes\App\Macros\Contracts\MacroContract;
use Dartmoon\LaravelLocalizedRoutes\App\RouteLocalizationService;
use Illuminate\Support\Facades\Route;

class RouteLocalizeMacro implements MacroContract
{
    protected static $methods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'any', 'match'];

    public static function register(): void
    {
        if (self::runningInOctane()) {
            self::registerRouteLocalizeMacroForOctane();
            self::registerRouteRegisterLocalizedRoutesForLocaleForOctane();
        } else {
            self::registerRouteLocalizeMacro();
        }
        
        collect(self::$methods)->each(fn ($method) => self::registerRouteMethodLocalizeMacro($method));
    }

    protected static function registerRouteLocalizeMacro(): void
    {
        Route::macro('localize', function ($callback) {
            // Prefix the current route with the right locale
            if (config('localized-routes.prefix_default_locale')) {
                Route::group(['prefix' => app()->getLocale(), 'locale' => app()->getLocale()], $callback);

                $isCurrentPrefixedByALocale = array_reduce(available_locales(), function ($carry, $locale) {
                    return $carry || str_starts_with(request()->path(), $locale . '/');
                }, false);
                
                if (!$isCurrentPrefixedByALocale) {
                    Route::redirect('/{any}', '/' . default_locale() . '/' . request()->path())->where('any', '.*')->fallback();
                }
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

    protected static function registerRouteLocalizeMacroForOctane(): void
    {
        Route::macro('localize', function ($callback) {
            $this->callbackToLocalize[] = [
                'groupStack' => $this->groupStack,
                'callback' => $callback,
            ];

            // Register localized routes for the other available locales
            collect(app(RouteLocalizationService::class)->getAvailableLocales(true))
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

    protected static function registerRouteRegisterLocalizedRoutesForLocaleForOctane(): void
    {
        Route::macro('registerLocalizedRoutesForLocale', function ($locale) {
            collect($this->callbackToLocalize)
                ->each(function ($callback) use ($locale) {
                    $this->groupStack = $callback['groupStack'];
                    // Prefix the current route with the right locale
                    if (config('localized-routes.prefix_default_locale')) {
                        Route::group(['prefix' => $locale, 'locale' => $locale], $callback['callback']);
            
                        $isCurrentPrefixedByALocale = array_reduce(available_locales(), function ($carry, $locale) {
                            return $carry || str_starts_with(request()->path(), $locale . '/');
                        }, false);
                        
                        if (!$isCurrentPrefixedByALocale) {
                            Route::redirect('/{any}', '/' . default_locale() . '/' . request()->path())->where('any', '.*')->fallback();
                        }
                    } else {
                        // Add compatibility with route helper
                        Route::when(!is_default_locale($locale), fn () => Route::group(['prefix' => $locale, 'locale' => $locale], $callback));
                        Route::when(is_default_locale($locale), fn () => Route::group(['locale' => $locale], $callback)); // Do not prefix the default locale
                    }
                });

            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
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
    
    protected static function runningInOctane(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE']) && ((int)$_SERVER['LARAVEL_OCTANE'] === 1);
    }
}
