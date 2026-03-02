<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;

final readonly class ServeRunner
{
    public function handle(Serve $runner, Closure $next): Serve
    {
        $runner->serve();

        return $next($runner);
    }
}
