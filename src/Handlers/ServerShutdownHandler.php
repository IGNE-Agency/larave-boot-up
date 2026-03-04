<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Handlers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Contracts\Server;

final class ServerShutdownHandler
{
    public function __construct(
        private readonly ExternalCommandManager $commandManager
    ) {
    }

    public function handleShutdown(
        Server $server,
        bool $shouldStopServer,
        string $serverName
    ): void {
        if ($shouldStopServer) {
            $server->cleanup();
            return;
        }

        $this->commandManager->stopAllProcesses();
    }
}
