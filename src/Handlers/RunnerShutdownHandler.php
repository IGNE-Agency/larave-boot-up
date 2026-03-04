<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Handlers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Contracts\Serve;

final class RunnerShutdownHandler
{
    public function __construct(
        private readonly ExternalCommandManager $commandManager
    ) {
    }

    public function handleShutdown(
        Serve $runner,
        bool $shouldStopRunner,
        string $runnerName
    ): void {
        if ($shouldStopRunner) {
            $runner->cleanup();
            return;
        }

        $this->commandManager->stopAllProcesses();
    }
}
