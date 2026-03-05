<?php

namespace Igne\LaravelBootstrap\Providers;

use Igne\LaravelBootstrap\Contracts\HasRuntimeFinalization;
use Igne\LaravelBootstrap\Enums\ProviderOption;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

final class ApplicationServeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProviderOption::APP_SERVE->value,
            fn ($app) => fn (mixed $context) => $app->make(Pipeline::class)
                ->send($context)
                ->through($this->pipes())
                ->then($this->afterBoot(...))
        );
    }

    protected function pipes(): array
    {
        return [
            \Igne\LaravelBootstrap\Pipelines\Bootstrap\EnsureLocalEnvironment::class,
            \Igne\LaravelBootstrap\Pipelines\Bootstrap\StartDevServer::class,
            \Igne\LaravelBootstrap\Pipelines\Bootstrap\CheckDependencies::class,
            \Igne\LaravelBootstrap\Pipelines\Bootstrap\CheckDatabase::class,
            \Igne\LaravelBootstrap\Pipelines\Bootstrap\DeployApplication::class,
            \Igne\LaravelBootstrap\Pipelines\Bootstrap\BuildOrWatchAssets::class,
            \Igne\LaravelBootstrap\Pipelines\Bootstrap\PostServeActions::class,
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
