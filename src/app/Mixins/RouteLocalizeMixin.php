<?php

namespace Dartmoon\LaravelLocalizedRoutes\App\Mixins;

use Dartmoon\LaravelLocalizedRoutes\App\RouteLocalizationService;
use Illuminate\Support\Facades\Route;

class RouteLocalizeMixin
{
    public array $callbackToLocalize = [];
    public array $callbackToLocalizeToCurrentLocale = [];

    public function localize()
    {
        $that = $this;
        return function ($callback) use ($that) {
            $that->callbackToLocalize ??= [];
            $that->callbackToLocalize[] = [
                'groupStack' => $this->groupStack,
                'callback' => $callback,
            ];

            return $this;
        };
    }

    public function localizeCurrentLocale()
    {
        $that = $this;
        return function ($callback) use ($that) {
            $that->callbackToLocalizeToCurrentLocale ??= [];
            $that->callbackToLocalizeToCurrentLocale[] = [
                'groupStack' => $this->groupStack,
                'callback' => $callback,
            ];

            return $this;
        };
    }

    public function registerLocalizedRoutesForLocale()
    {
        $that = $this;
        return function ($currentLocale) use ($that) {
            collect($that->callbackToLocalize ?? [])
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

            collect($that->callbackToLocalizeToCurrentLocale ?? [])
                ->each(function ($callback) use ($currentLocale) {
                    // Restore the group stack
                    $this->groupStack = $callback['groupStack'];

                    Route::group(['prefix' => $currentLocale, 'locale' => $currentLocale], $callback['callback']);
                });

            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();

            return $this;
        };
    }

    public function getLocalized()
    {
        return fn ($uri, $action = null) => $this->methodLocalized('get', $uri, $action);
    }
    
    public function postLocalized()
    {
        return fn ($uri, $action = null) => $this->methodLocalized('post', $uri, $action);
    }
    
    public function putLocalized()
    {
        return fn ($uri, $action = null) => $this->methodLocalized('put', $uri, $action);
    }
    
    public function patchLocalized()
    {
        return fn ($uri, $action = null) => $this->methodLocalized('patch', $uri, $action);
    }
    
    public function deleteLocalized()
    {
        return fn ($uri, $action = null) => $this->methodLocalized('delete', $uri, $action);
    }
    
    public function optionsLocalized()
    {
        return fn ($uri, $action = null) => $this->methodLocalized('options', $uri, $action);
    }
    
    public function anyLocalized()
    {
        return fn ($uri, $action = null) => $this->methodLocalized('any', $uri, $action);
    }
    
    public function matchLocalized()
    {
        return fn ($uri, $action = null) => $this->methodLocalized('match', $uri, $action);
    }

    public function methodLocalized()
    {
        return function ($method, $uri, $action = null) {
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
        };
    }
}
