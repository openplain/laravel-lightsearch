<?php

namespace Ktr\LightSearch;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;

class LightSearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/lightsearch.php',
            'lightsearch'
        );
    }

    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/lightsearch.php' => config_path('lightsearch.php'),
        ], 'lightsearch-config');

        // Publish migration if not already published
        if (!class_exists('CreateLightsearchIndexTable')) {
            $timestamp = date('Y_m_d_His');
            $this->publishes([
                __DIR__.'/../database/migrations/create_lightsearch_index_table.php' => database_path("migrations/{$timestamp}_create_lightsearch_index_table.php"),
            ], 'lightsearch-migrations');
        }

        // Register the custom Scout engine
        $this->app->make(EngineManager::class)->extend('lightsearch', function () {
            // Merge lightsearch config with scout.lightsearch for backwards compatibility
            $config = array_merge(
                config('lightsearch', []),
                config('scout.lightsearch', [])
            );

            return new LightSearchEngine($config);
        });

        // Add fuzzy search macro to Scout Builder
        // Note: Fuzzy search is automatically enabled when PostgreSQL pg_trgm extension is available
        // This macro is only needed to adjust the similarity threshold (default: 0.3)
        Builder::macro('fuzzy', function (float $threshold = 0.3) {
            /** @var Builder $this */
            $this->options['fuzzy_threshold'] = $threshold;

            return $this;
        });
    }
}
