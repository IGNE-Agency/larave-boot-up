<?php

namespace Igne\LaravelBootstrap\Bootstrap;

final class DatabaseSetupBootstrap extends PipelineBootstrap
{
    protected function pipes(): array
    {
        return [
            \Igne\LaravelBootstrap\Pipelines\Database\CheckDatabaseSetup::class,
            \Igne\LaravelBootstrap\Pipelines\Database\EnsureDatabaseExists::class,
            \Igne\LaravelBootstrap\Pipelines\Database\VerifyDatabaseConnection::class,
            \Igne\LaravelBootstrap\Pipelines\Database\RunInitialMigrations::class,
        ];
    }
}
