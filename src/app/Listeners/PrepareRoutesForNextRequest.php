<?php

namespace Dartmoon\LaravelLocalizedRoutes\App\Listeners;

use Dartmoon\LaravelLocalizedRoutes\App\RouteLocalizationService;
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
        $routeLocalizationService = $event->sandbox->make(RouteLocalizationService::class);
        $routeLocalizationService->setLocaleFromRequest();

        Route::registerLocalizedRoutesForLocale($event->sandbox->getLocale());
    }
}