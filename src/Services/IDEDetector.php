<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Services;

use Igne\LaravelBootstrap\Enums\IDE;

final class IDEDetector
{
    private ?IDE $currentIDE = null;

    public function __construct()
    {
        $this->currentIDE = IDE::detectCurrent();
    }

    public function isRunningInIDE(): bool
    {
        return $this->currentIDE !== null;
    }

    public function getIDE(): ?IDE
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
