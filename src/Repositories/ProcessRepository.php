<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Repositories;

use Symfony\Component\Process\Process;

final class ProcessRepository
{
    private array $processes = [];

    public function register(Process $process): void
    {
        if ($process->isRunning()) {
            $this->processes[] = $process;
        }
    }

    public function stopAll(): void
    {
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
    }

    public function cleanup(): void
    {
        $this->processes = array_filter(
            $this->processes,
            fn($process) => $process->isRunning()
        );
    }

    public function getLastProcess(): ?Process
    {
        $lastProcess = end($this->processes);

        return $lastProcess ?: null;
    }
}
