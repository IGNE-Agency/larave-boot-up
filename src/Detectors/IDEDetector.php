<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Detectors;

use Igne\LaravelBootstrap\Enums\IDEOption;

final class IDEDetector
{
    private ?IDEOption $currentIDE = null;

    public function __construct()
    {
        $this->currentIDE = IDEOption::detectCurrent();
    }

    public function isRunningInIDE(): bool
    {
        return $this->currentIDE !== null;
    }

    public function getIDE(): ?IDEOption
    {
        return $this->currentIDE;
    }

    public function getIDETerminalCommand(string $command): ?string
    {
        if (!$this->currentIDE) {
            return null;
        }

        return $this->currentIDE->getTerminalCommand($command);
    }
}
