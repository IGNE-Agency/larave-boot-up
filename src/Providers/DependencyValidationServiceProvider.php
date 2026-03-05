<?php

namespace Igne\LaravelBootstrap\Providers;

use Igne\LaravelBootstrap\Contracts\HasRuntimeFinalization;
use Igne\LaravelBootstrap\Enums\ProviderOption;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

final class DependencyValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProviderOption::DEPENDENCIES->value,
            fn ($app) => fn (mixed $context) => $app->make(Pipeline::class)
                ->send($context)
                ->through($this->pipes())
                ->then($this->afterBoot(...))
        );
    }

    protected function pipes(): array
    {
        return [
            \Igne\LaravelBootstrap\Pipelines\Dependencies\ValidateServerServices::class,
            \Igne\LaravelBootstrap\Pipelines\Dependencies\EnsureEnvFileExists::class,
            \Igne\LaravelBootstrap\Pipelines\Dependencies\GenerateAppKey::class,
            \Igne\LaravelBootstrap\Pipelines\Dependencies\ValidateTools::class,
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
