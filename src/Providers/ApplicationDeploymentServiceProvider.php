<?php

namespace Igne\LaravelBootstrap\Providers;

use Igne\LaravelBootstrap\Contracts\HasRuntimeFinalization;
use Igne\LaravelBootstrap\Enums\ProviderOption;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

final class ApplicationDeploymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProviderOption::APP_DEPLOY->value,
            fn ($app) => fn (mixed $context) => $app->make(Pipeline::class)
                ->send($context)
                ->through($this->pipes())
                ->then($this->afterBoot(...))
        );
    }

    protected function pipes(): array
    {
        return [
            \Igne\LaravelBootstrap\Pipelines\Deploy\InstallComposerDependencies::class,
            \Igne\LaravelBootstrap\Pipelines\Deploy\InstallFrontendDependencies::class,
            \Igne\LaravelBootstrap\Pipelines\Deploy\RunCustomCommandsBeforeMigrations::class,
            \Igne\LaravelBootstrap\Pipelines\Deploy\RunDatabaseMigrations::class,
            \Igne\LaravelBootstrap\Pipelines\Deploy\RunCustomCommandsAfterMigrations::class,
            \Igne\LaravelBootstrap\Pipelines\Deploy\CacheFrameworkFiles::class,
            \Igne\LaravelBootstrap\Pipelines\Deploy\StartQueueWorker::class,
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
