<?php

namespace Dartmoon\LaravelLocalizedRoutes\App\Listeners;

use Dartmoon\LaravelLocalizedRoutes\App\RouteLocalizationService;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;

class PrepareRoutesForNextRequest
{    
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle($event): void
    {
        $this->reloadRoutes($event->sandbox);

        $routeLocalizationService = $event->sandbox->make(RouteLocalizationService::class);
        $routeLocalizationService->setLocaleFromRequest();

        Route::registerLocalizedRoutesForLocale($event->sandbox->getLocale());
    }

    protected function reloadRoutes($app): void
    {
        // Realod the routes from the cache
        require $app->getCachedRoutesPath();

        $collection = new RouteCollection();
        collect(Route::getRoutes()->getRoutes())
            ->each(fn ($route) => $collection->add($route));

        Route::setRoutes($collection);
    }
}