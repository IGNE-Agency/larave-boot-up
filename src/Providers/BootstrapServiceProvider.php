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

        $this->app->register(ApplicationServeServiceProvider::class);
        $this->app->register(ApplicationDeploymentServiceProvider::class);
        $this->app->register(DatabaseSetupServiceProvider::class);
        $this->app->register(DependencyValidationServiceProvider::class);
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
