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

        $this->app->singleton(
            \Igne\LaravelBootstrap\Bootstrap\ApplicationServeBootstrap::class,
            fn ($app) => new \Igne\LaravelBootstrap\Bootstrap\ApplicationServeBootstrap($app->make(\Illuminate\Pipeline\Pipeline::class))
        );

        $this->app->singleton(
            \Igne\LaravelBootstrap\Bootstrap\ApplicationDeploymentBootstrap::class,
            fn ($app) => new \Igne\LaravelBootstrap\Bootstrap\ApplicationDeploymentBootstrap($app->make(\Illuminate\Pipeline\Pipeline::class))
        );

        $this->app->singleton(
            \Igne\LaravelBootstrap\Bootstrap\DatabaseSetupBootstrap::class,
            fn ($app) => new \Igne\LaravelBootstrap\Bootstrap\DatabaseSetupBootstrap($app->make(\Illuminate\Pipeline\Pipeline::class))
        );

        $this->app->singleton(
            \Igne\LaravelBootstrap\Bootstrap\DependencyValidationBootstrap::class,
            fn ($app) => new \Igne\LaravelBootstrap\Bootstrap\DependencyValidationBootstrap($app->make(\Illuminate\Pipeline\Pipeline::class))
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bootstrap.php' => config_path('bootstrap.php'),
            ], 'bootstrap-config');

            $this->commands([
                \Igne\LaravelBootstrap\Console\Commands\ServeApplicationCommand::class,
                \Igne\LaravelBootstrap\Console\Commands\ShutdownApplicationCommand::class,
                \Igne\LaravelBootstrap\Console\Commands\Helpers\DeployCommand::class,
                \Igne\LaravelBootstrap\Console\Commands\Helpers\ValidateDatabaseCommand::class,
                \Igne\LaravelBootstrap\Console\Commands\Helpers\ValidateDependenciesCommand::class,
            ]);
        }
    }
}
