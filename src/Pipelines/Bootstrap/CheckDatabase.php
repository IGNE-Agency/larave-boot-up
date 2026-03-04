<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;
use Illuminate\Support\Facades\Artisan;

final readonly class CheckDatabase
{
    public function handle(Serve $environment, Closure $next): Serve
    {
        Artisan::call('check:database', [
            'runner' => $environment->getRunner(),
        ], $environment->getOutput());

        return $next($environment);
    }
}
