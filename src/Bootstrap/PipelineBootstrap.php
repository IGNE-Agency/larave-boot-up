<?php

namespace Igne\LaravelBootstrap\Bootstrap;

use Illuminate\Pipeline\Pipeline;

abstract class PipelineBootstrap
{
    protected bool $booted = false;

    protected mixed $context = null;

    public function __construct(protected Pipeline $pipeline) {}

    public function register(mixed $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function boot(): mixed
    {
        if ($this->booted) {
            return $this->context;
        }

        $this->booted = true;

        return $this->pipeline
            ->send($this->context)
            ->through($this->pipes())
            ->then(fn ($passable) => $this->afterBoot($passable));
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    abstract protected function pipes(): array;

    protected function afterBoot(mixed $passable): mixed
    {
        return $passable;
    }
}
