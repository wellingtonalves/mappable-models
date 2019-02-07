<?php

namespace Reddes\MappableModels;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/mappable-models.php' => config_path('mappable-models.php'),
            __DIR__.'/../config/example.php' => base_path('database/mappings/example.php'),
        ], 'mappable-models');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->mergeConfigFrom( __DIR__.'/../config/mappable-models.php', 'mappable-models');
    }

    /**
     * Provides the package
     *
     * @return array
     */
    public function provides()
    {
        return ['mappable-models'];
    }
}
