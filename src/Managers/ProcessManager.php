<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Managers;

use Igne\LaravelBootstrap\Enums\OSCommand;

final class ProcessManager
{
    public function isRunning(int $pid): bool
    {
        $checkCommand = $this->buildCheckCommand($pid);

        if ($checkCommand === null) {
            return false;
        }

        $output = shell_exec($checkCommand);

        return $this->processExists($output, $pid);
    }

    public function kill(int $pid): bool
    {
        if (! $this->isRunning($pid)) {
            return false;
        }

        $this->executeKillCommand($pid);
        $this->waitForTermination();

        return ! $this->isRunning($pid);
    }

    private function buildCheckCommand(int $pid): ?string
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => "tasklist /FI \"IMAGENAME eq {$pid}.exe\" /NH",
            default => "pgrep -f {$pid}",
        };

        return $command;
    }

    private function processExists(?string $output, int $pid): bool
    {
        return ! empty($output) && str_contains((string) $output, (string) $pid);
    }

    private function executeKillCommand(int $pid): void
    {
        OSCommand::KILL_PROCESS->forPid($pid)->call();
    }

    private function waitForTermination(): void
    {
        usleep(100000);
    }
}
