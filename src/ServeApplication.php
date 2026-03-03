<?php

namespace Igne\LaravelBootstrap;

use Igne\LaravelBootstrap\Contracts\Serve;

final class ServeApplication
{
    public function __construct(protected Serve $runner)
    {
    }

    public function boot(): void
    {
        app(\Illuminate\Pipeline\Pipeline::class)
            ->send($this->runner)
            ->through([
                \Igne\LaravelBootstrap\Pipelines\Bootstrap\ServeRunner::class,
                \Igne\LaravelBootstrap\Pipelines\Bootstrap\CheckDependencies::class,
                \Igne\LaravelBootstrap\Pipelines\Bootstrap\EnsureLocalEnvironment::class,
                \Igne\LaravelBootstrap\Pipelines\Bootstrap\CheckDatabase::class,
                \Igne\LaravelBootstrap\Pipelines\Bootstrap\DeployApplication::class,
                \Igne\LaravelBootstrap\Pipelines\Bootstrap\BuildOrWatchAssets::class,
                \Igne\LaravelBootstrap\Pipelines\Bootstrap\PostServeActions::class,
            ])
            ->thenReturn();
    }
}
