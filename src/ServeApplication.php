<?php

namespace Igne\LaravelBootstrap;

use Igne\LaravelBootstrap\Contracts\Server;

final class ServeApplication
{
    public function __construct(protected Server $server)
    {
    }

    public function boot(): void
    {
        app(\Illuminate\Pipeline\Pipeline::class)
            ->send($this->server)
            ->through([
                \Igne\LaravelBootstrap\Pipelines\Bootstrap\StartDevServer::class,
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
