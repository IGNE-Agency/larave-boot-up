<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Server;

final readonly class PostServeActions
{
    public function handle(Server $server, Closure $next): Server
    {
        $server->postServe();

        return $next($server);
    }
}
