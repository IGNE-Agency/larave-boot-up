<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Traits;

use Igne\LaravelBootstrap\Enums\OSCommand;

trait OpensTerminalCommands
{
    protected function executeInSeparateTerminal(string $command): void
    {
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
