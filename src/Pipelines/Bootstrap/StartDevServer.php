<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Server;

final readonly class StartDevServer
{
    public function handle(Server $server, Closure $next): Server
    {
        $server->serve();

        return $next($server);
    }
}
