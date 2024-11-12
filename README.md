# Laravel Localized Routes

A simple package to localize the routes of Laravel. This pakage adds some macros to the `Route` facade to allow the localization. There are also some helpers.

## Installation

```bash
composer require dartmoon/laravel-localized-routes
```

## Usage
1. Group all the routes you want to translate inside a `Route::localize(...)`. This must be put at the topmost group level of your routes. 

```php
Route::localize(function () {
    // Put here all the routes you want to localize
});
```

2. For each route that you want to localize change the method with the localized one. The localized version has a `Localized` suffix.

E.g. if this is your route file
```php
Route::group(function () {
    Route::get('/home', ...);
    Route::post('/update-profile', ...);

    Route::get('/do/not/localize');
});

Route::get('/external');
```

Then it must become
```php
Route::localize(function () {
    Route::getLocalized('/home', ...);
    Route::postLocalized('/update-profile', ...);

    Route::get('/do/not/translate/but/prefix');
});

Route::get('/external');
```

3. You can now translate all your routes using the Laravel translation service. Inside the your lang folder (eg. `/lang/it`) create a `routes.php` file.

```php
<?php

return [
    '/home' => '/home-translated',
    '/update-profile' => '/aggiorna-profilo',
];
```

## Customizing the available languages
First you need to publish the config.

```bash
php artisan vendor:publish --provider="Dartmoon\LaravelLocalizedRoutes\LaravelLocalizedRoutesServiceProvider"
```

Then you will find two new files (`locales.php` and `localized-routes.php`) files inside your `config` folder.

The file `locales.php` contains the list of the available locales. By default it is as follows:
```php
<?php
/**
 * Return enabled locales
 */

return [
    'available' => [
        // 'locale' => 'Name of the locale'
        'en' => 'EN',
        'it' => 'IT',
    ],
    'default' => 'en', // Default locale to be used
];
```

You can now edit the `available` with the locale you want to enable.

The file `localized-routes.php` contains the configuration of the package. By default it is as follows:
```php
<?php
/**
 * Return the configuration for the localized routes
 */

return [
    'prefix_default' => false, // If true the default locale will be prefixed to the routes
];
```

You can now edit the `prefix_default` to `true` if you want to prefix the default locale to the routes.


## Named routes
This package supports named routes out of the box and adds some useful prefixes.

E.g. Let's suppose these are your routes.
```php
Route::localize(function () {
    Route::getLocalized('/home', ...)->name('home');
});
```

And this is your translation
```php 
<?php

return [
    '/home' => '/home-translated',
];
```

Then you can simply do as follows:
```php
route('home'); // If you have the "en" locale loaded then '/home', if you have the "it" locale loaded than it will be '/home-translated'
route('it.home'); // Will return '/home-translated'. This route will not be defined if the current locale is "it"!
route('en.home'); // Will return '/home'. This route will not be defined if the current locale is "en"!
```

## Helpers
- `route_localized($name, $parameters = [], $locale = null, $absolute = true)` it behaves exactly as the `route` helper of Laravel, but it allows you to specify the locale

- `url_localized($url, $locale = null)` it allows you to localize an URL

- `current_localized($locale = null)` it returns the current URL or route localized

- `available_locales()` returns the available locales, without their names

- `is_default_locale($locale)` returns true if the specified locale is the default one

- `is_current_locale_default` returns true if the current locale is the default one

- `locale_name($locale, $default = null)` returns the locale name for the specified locale

- `default_locale()` returns the default locale

- `available_alternates()` returns the available alternates for the available locales

## Using a different LocaleProvider
By default the package uses the `Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\DefaultLocaleProvider` to get the current locale from the `locales.php` config file. 

If you want to use a different provider to get the available locales from somewhere else, you can do so by creating a new class that implements the `Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\Contracts\LocaleProviderContract` interface and then binding it in the service container.

```php
<?php

namespace App\LocaleProviders;

use Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\Contracts\LocaleProviderContract;

class MyCustomLocaleProvider implements LocaleProviderContract
{
    public function getDefaultLocale(): string
    {
        return 'en';
    }

    public function getAvailableLocales(): array
    {
        return [
            'en' => 'EN',
            'it' => 'IT',
        ];
    }

    public function getLocaleName(string $locale, string $default = null): string
    {
        return $this->getAvailableLocales()[$locale] ?? $default;
    }
}
```

Then you can bind it in the service container in the `AppServiceProvider` class.

```php
<?php

namespace App\Providers;

use App\LocaleProviders\MyCustomLocaleProvider;
use Dartmoon\LaravelLocalizedRoutes\App\LocaleProviders\Contracts\LocaleProviderContract;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            LocaleProviderContract::class,
            MyCustomLocaleProvider::class
        );
    }
}
```

## Using Laravel Octane
If you are using Laravel Octane you need to register the following listener to the `config/octane.php` file.

```php
<?php

return [
    ...
    'listeners' => [
        ...
        RequestReceived::class => [
            ...
            \Dartmoon\LaravelLocalizedRoutes\App\Listeners\PrepareRoutesForNextRequest::class,
        ],
        ...    
    ],
    ...
];
```

And then you can use the package as usual.

## License

This project is licensed under the MIT License - see the LICENSE.md file for details