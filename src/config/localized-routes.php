<?php
/**
 * Laravel Localized Routes settings
 */

return [
    'prefix_default_locale' => false, 
    'register_routes_on_boot' => env('OCTANE_SERVER') === null, // Only register routes on boot if not using Octane
];
