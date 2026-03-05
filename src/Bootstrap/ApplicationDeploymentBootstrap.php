<?php

namespace Igne\LaravelBootstrap\Bootstrap;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;

final class ApplicationDeploymentBootstrap extends PipelineBootstrap
{
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
        if ($passable instanceof InterruptibleCommand) {
            $passable->finalizeRuntime();
        }

        return $passable;
    }
}
