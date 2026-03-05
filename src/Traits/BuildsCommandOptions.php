<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Traits;

trait BuildsCommandOptions
{
    protected function buildOptions(array $options): array
    {
        return collect($options)
            ->mapWithKeys(
                fn ($value, $key) => \is_int($key)
                    ? [$value => true]
                    : [$key => $value]
            )
            ->map(
                fn ($value, $key) => $value === false || $value === null
                    ? null
                    : (\is_bool($value) ? $key : "{$key}={$value}")
            )
            ->filter()
            ->values()
            ->all();
    }
}
