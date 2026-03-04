<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Factories;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Server;
use Igne\LaravelBootstrap\Servers\HerdServer;
use Igne\LaravelBootstrap\Servers\LaravelServer;
use Igne\LaravelBootstrap\Servers\SailServer;

final class ServerFactory
{
    public function create(string $serverName, InterruptibleCommand $command): Server
    {
        return match ($serverName) {
            'herd' => new HerdServer($command),
            'sail' => new SailServer($command),
            'laravel' => new LaravelServer($command),
            default => throw new \InvalidArgumentException("Unknown server: {$serverName}")
        };
    }
}
