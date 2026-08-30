<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Jetstream::ignoreRoutes();
    }

    /**
     * Keep the existing application-owned auth and settings pages.
     */
    public function boot(): void
    {
        // The package remains available for its feature configuration and
        // integrations, while this app keeps its own styled auth/settings UI.
    }
}
