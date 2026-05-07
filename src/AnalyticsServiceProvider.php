<?php

declare(strict_types=1);

namespace BrewAndBytes\AcornAnalytics;

use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/analytics.php', 'analytics');

        $this->app->singleton(
            Analytics::class,
            fn ($app): Analytics => Analytics::make($app)
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/analytics.php' => $this->app->configPath('analytics.php'),
        ], 'analytics-config');

        $this->app->make(Analytics::class);
    }
}
