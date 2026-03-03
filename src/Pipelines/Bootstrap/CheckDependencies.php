<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;
use Illuminate\Support\Facades\Artisan;

final readonly class CheckDependencies
{
    public function handle(Serve $runner, Closure $next): Serve
    {
        Artisan::call('check:dependencies', [
            'runner' => $runner->getRunner()->value,
        ], $runner->getOutput());

        return $next($runner);
    }
}
