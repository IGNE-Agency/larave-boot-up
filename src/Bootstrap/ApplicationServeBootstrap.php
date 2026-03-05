<?php

namespace Igne\LaravelBootstrap\Bootstrap;

final class ApplicationServeBootstrap extends PipelineBootstrap
{
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
}
