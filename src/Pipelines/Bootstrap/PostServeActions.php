<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;

final readonly class PostServeActions
{
    public function handle(Serve $runner, Closure $next): Serve
    {
        $runner->postServe();

        return $next($runner);
    }
}
