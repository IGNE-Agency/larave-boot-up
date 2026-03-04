<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Server;
use Illuminate\Support\Facades\Artisan;

final readonly class DeployApplication
{
    public function handle(Server $server, Closure $next): Server
    {
        Artisan::call('app:deploy', [
            'server' => $server->getServer(),
        ], $server->getOutput());

        return $next($server);
    }
}
