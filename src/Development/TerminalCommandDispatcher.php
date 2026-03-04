<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Development;

final class TerminalCommandDispatcher
{
    public function __construct(
        private readonly \Igne\LaravelBootstrap\Resolvers\TerminalCommandResolver $commandResolver,
        private readonly BackgroundCommandRunner $commandRunner,
        private readonly \Igne\LaravelBootstrap\Managers\ProcessTrackingManager $trackingManager
    ) {
    }

    public function executeInSeparateTerminal(string $command): void
    {
        $terminalCommand = $this->commandResolver->resolveCommand($command);

        if ($terminalCommand === null) {
            return;
        }

        $pid = $this->commandRunner->executeInBackground($terminalCommand);

        if ($pid !== null) {
            $this->trackingManager->trackProcess($command, $pid);
        }
    }

    public function canOpenTerminal(): bool
    {
        return $this->commandResolver->canOpenTerminal();
    }
}
