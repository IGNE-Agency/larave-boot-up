<?php

namespace Igne\LaravelBootstrap;

use Igne\LaravelBootstrap\Contracts\Server;

final class ServeApplication
{
    public function __construct(protected Server $server) {}

    public function boot(): void
    {
        app(\Illuminate\Pipeline\Pipeline::class)
            ->send($this->server)
            ->through([
                Pipelines\Bootstrap\EnsureLocalEnvironment::class,
                Pipelines\Bootstrap\StartDevServer::class,
                Pipelines\Bootstrap\CheckDependencies::class,
                Pipelines\Bootstrap\CheckDatabase::class,
                Pipelines\Bootstrap\DeployApplication::class,
                Pipelines\Bootstrap\BuildOrWatchAssets::class,
                Pipelines\Bootstrap\PostServeActions::class,
            ])
            ->thenReturn();
    }
}
