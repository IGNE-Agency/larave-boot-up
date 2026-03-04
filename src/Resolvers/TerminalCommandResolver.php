<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Resolvers;

use Igne\LaravelBootstrap\Detectors\IDEDetector;
use Igne\LaravelBootstrap\Enums\OSCommand;

final class TerminalCommandResolver
{
    public function __construct(
        private readonly IDEDetector $ideDetector
    ) {
    }

    public function resolveCommand(string $command): ?string
    {
        if ($this->ideDetector->isRunningInIDE()) {
            $ideCommand = $this->ideDetector->getIDETerminalCommand($command);

            if ($ideCommand !== null) {
                return $ideCommand;
            }
        }

        return OSCommand::OPEN_TERMINAL
            ->withCommand($command)
            ->execute();
    }

    public function canOpenTerminal(): bool
    {
        return OSCommand::OPEN_TERMINAL->canExecute();
    }
}
