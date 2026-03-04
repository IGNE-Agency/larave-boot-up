<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Parsers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class CommandParser
{
    public function normalize(string|array $command): Collection
    {
        return collect(Arr::wrap($command))
            ->flatten()
            ->flatMap(
                fn($item) => \is_string($item)
                ? Str::of($item)->trim()->explode(' ')
                : Arr::wrap($item)
            )
            ->filter()
            ->values();
    }
}
