<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;
use Illuminate\Support\Facades\Artisan;

final readonly class DeployApplication
{
    public function handle(Serve $runner, Closure $next): Serve
    {
        Artisan::call('app:deploy', [
            'runner' => $runner->getRunner(),
        ], $runner->getOutput());

        return $next($runner);
    }
}
