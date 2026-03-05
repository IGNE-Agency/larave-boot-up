<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Traits;

use Igne\LaravelBootstrap\Detectors\IDEDetector;
use Igne\LaravelBootstrap\Enums\OSCommand;

trait OpensTerminalCommands
{
    protected function executeInSeparateTerminal(string $command): void
    {
        $ideDetector = new IDEDetector;

        if ($ideDetector->isRunningInIDE()) {
            $ideCommand = $ideDetector->getIDETerminalCommand($command);

            if ($ideCommand) {
                shell_exec("{$ideCommand} > /dev/null 2>&1 &");

                return;
            }
        }

        $terminalCommand = OSCommand::OPEN_TERMINAL
            ->withCommand($command)
            ->execute();

        if ($terminalCommand) {
            shell_exec("{$terminalCommand} > /dev/null 2>&1 &");
        }
    }

    protected function canOpenTerminal(): bool
    {
        return OSCommand::OPEN_TERMINAL->canExecute();
    }
}
