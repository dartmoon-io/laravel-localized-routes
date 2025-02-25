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
        self::registerRouteLocalizeMacro();
        self::registerRouteLocalizeCurrentLocaleMacro();
        self::registerRouteRegisterLocalizedRoutesForLocale();
        
        collect(self::$methods)->each(fn ($method) => self::registerRouteMethodLocalizeMacro($method));
    }

    protected static function registerRouteLocalizeMacro(): void
    {
        Route::macro('localize', function ($callback) {
            $this->callbackToLocalize ??= [];
            $this->callbackToLocalize[] = [
                'groupStack' => $this->groupStack,
                'callback' => $callback,
            ];

            return $this;
        });
    }

    protected static function registerRouteLocalizeCurrentLocaleMacro(): void
    {
        Route::macro('localizeCurrentLocale', function ($callback) {
            $this->callbackToLocalizeToCurrentLocale ??= [];
            $this->callbackToLocalizeToCurrentLocale[] = [
                'groupStack' => $this->groupStack,
                'callback' => $callback,
            ];

            return $this;
        });
    }

    protected static function registerRouteRegisterLocalizedRoutesForLocale(): void
    {
        Route::macro('registerLocalizedRoutesForLocale', function ($currentLocale) {
            collect($this->callbackToLocalize ?? [])
                ->each(function ($callback) use ($currentLocale) {
                    // Restore the group stack
                    $this->groupStack = $callback['groupStack'];

                    // Prefix the current route with the right locale
                    if (config('localized-routes.prefix_default_locale')) {
                        Route::group(['prefix' => $currentLocale, 'locale' => $currentLocale], $callback['callback']);

                        $isCurrentPrefixedByALocale = array_reduce(app(RouteLocalizationService::class)->getAvailableLocales(), function ($carry, $locale) {
                            return $carry || str_starts_with(request()->path(), $locale . '/') || request()->path() == $locale;
                        }, false);
                        
                        if (!$isCurrentPrefixedByALocale) {
                            Route::redirect('/{any}', '/' . default_locale() . '/' . request()->path())->where('any', '.*')->fallback();
                        }
                    } else {
                        // Add compatibility with route helper
                        Route::when(!is_default_locale($currentLocale), fn () => Route::group(['prefix' => $currentLocale, 'locale' => $currentLocale], $callback['callback']));
                        Route::when(is_default_locale($currentLocale), fn () => Route::group(['locale' => $currentLocale], $callback['callback'])); // Do not prefix the default locale
                    }

                    // Register localized routes for the other available locales
                    collect(app(RouteLocalizationService::class)->getAvailableLocales())
                        ->filter(fn ($locale) => $locale != $currentLocale)
                        ->each(function ($locale) use ($callback) {
                            if (config('localized-routes.prefix_default_locale')) {
                                Route::group(['prefix' => $locale, 'as' => "{$locale}.", 'locale' => $locale], $callback['callback']);
                            } else {
                                // Add compatibility with route helper
                                Route::when(!is_default_locale($locale), fn () => Route::group(['prefix' => $locale, 'as' => "{$locale}.", 'locale' => $locale], $callback['callback']));
                                Route::when(is_default_locale($locale), fn () => Route::group(['as' => "{$locale}.", 'locale' => $locale], $callback['callback'])); // Do not prefix the default locale
                            }
                        });
                });

            collect($this->callbackToLocalizeToCurrentLocale ?? [])
                ->each(function ($callback) use ($currentLocale) {
                    // Restore the group stack
                    $this->groupStack = $callback['groupStack'];

                    Route::group(['prefix' => $currentLocale, 'locale' => $currentLocale], $callback['callback']);
                });

            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();

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
