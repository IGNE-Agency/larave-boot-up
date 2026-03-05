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
        return OSCommand::CHECK_PROCESS
            ->forProcess((string) $pid)
            ->execute();
    }

    private function processExists(?string $output, int $pid): bool
    {
        return ! empty($output) && str_contains((string) $output, (string) $pid);
    }

    private function executeKillCommand(int $pid): void
    {
        $killCommand = OSCommand::KILL_PROCESS->forPid($pid)->execute();

        if (! empty($killCommand)) {
            shell_exec($killCommand);
        }
    }

    private function waitForTermination(): void
    {
        usleep(100000);
    }
}
