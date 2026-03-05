<?php

namespace Igne\LaravelBootstrap\Bootstrap;

final class DependencyValidationBootstrap extends PipelineBootstrap
{
    protected function pipes(): array
    {
        return [
            \Igne\LaravelBootstrap\Pipelines\Dependencies\ValidateServerServices::class,
            \Igne\LaravelBootstrap\Pipelines\Dependencies\EnsureEnvFileExists::class,
            \Igne\LaravelBootstrap\Pipelines\Dependencies\GenerateAppKey::class,
            \Igne\LaravelBootstrap\Pipelines\Dependencies\ValidateTools::class,
        ];
    }
}
