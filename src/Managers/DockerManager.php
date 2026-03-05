<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Managers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Enums\OSCommand;

final class DockerManager
{
    public function __construct(
        private readonly ExternalCommandManager $commandManager
    ) {}

    public function isRunning(): bool
    {
        return $this->commandManager->isCommandRunning('docker info');
    }

    public function start(): void
    {
        $this->commandManager->call(OSCommand::START_DOCKER->execute());
    }
}
