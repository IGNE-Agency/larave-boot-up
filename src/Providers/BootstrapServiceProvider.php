<?php

namespace Igne\LaravelBootstrap\Providers;

use Illuminate\Support\ServiceProvider;

final class BootstrapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/bootstrap.php',
            'bootstrap'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bootstrap.php' => config_path('bootstrap.php'),
            ], 'bootstrap-config');

            $this->commands([
                \Igne\LaravelBootstrap\Console\Commands\AppBootstrap::class,
                \Igne\LaravelBootstrap\Console\Commands\AppDown::class,
                \Igne\LaravelBootstrap\Console\Commands\Helpers\AppDeployCommand::class,
                \Igne\LaravelBootstrap\Console\Commands\Helpers\DatabaseCheckCommand::class,
                \Igne\LaravelBootstrap\Console\Commands\Helpers\DependencyCheckCommand::class,
            ]);
        }
    }
}
