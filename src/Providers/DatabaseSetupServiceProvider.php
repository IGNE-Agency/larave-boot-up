<?php

namespace Igne\LaravelBootstrap\Providers;

use Igne\LaravelBootstrap\Contracts\HasRuntimeFinalization;
use Igne\LaravelBootstrap\Enums\ProviderOption;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

final class DatabaseSetupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProviderOption::DATABASE->value,
            fn ($app) => fn (mixed $context) => $app->make(Pipeline::class)
                ->send($context)
                ->through($this->pipes())
                ->then($this->afterBoot(...))
        );
    }

    protected function pipes(): array
    {
        return [
            \Igne\LaravelBootstrap\Pipelines\Database\CheckDatabaseSetup::class,
            \Igne\LaravelBootstrap\Pipelines\Database\EnsureDatabaseExists::class,
            \Igne\LaravelBootstrap\Pipelines\Database\VerifyDatabaseConnection::class,
            \Igne\LaravelBootstrap\Pipelines\Database\RunInitialMigrations::class,
        ];
    }

    protected function afterBoot(mixed $passable): mixed
    {
        if ($passable instanceof HasRuntimeFinalization) {
            $passable->finalizeRuntime();
        }

        return $passable;
    }
}
