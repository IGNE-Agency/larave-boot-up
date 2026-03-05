<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Factories;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Server;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Servers\HerdServer;
use Igne\LaravelBootstrap\Servers\LaravelServer;
use Igne\LaravelBootstrap\Servers\SailServer;

final class ServerFactory
{
    public function create(DevServerOption $serverOption, InterruptibleCommand $command): Server
    {
        return match ($serverOption) {
            DevServerOption::HERD => new HerdServer($command),
            DevServerOption::SAIL => new SailServer($command),
            DevServerOption::LARAVEL => new LaravelServer($command),
            default => throw new \InvalidArgumentException("Not registered server: {$serverOption->value}")
        };
    }
}
