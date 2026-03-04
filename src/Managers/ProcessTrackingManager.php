<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Managers;

final class ProcessTrackingManager
{
    public function __construct(
        private readonly \Igne\LaravelBootstrap\Repositories\ProcessFileRepository $repository,
        private readonly ProcessManager $processManager,
        private readonly \Igne\LaravelBootstrap\Parsers\CommandExtractor $commandExtractor
    ) {
    }

    public function trackProcess(string $command, int $pid): void
    {
        $processes = $this->getTrackedProcesses();
        $processes[] = $this->buildProcessRecord($command, $pid);
        $this->repository->save($processes);
    }

    public function getTrackedProcesses(): array
    {
        $processes = $this->repository->load();

        return $this->filterRunningProcesses($processes);
    }

    public function killAllTrackedProcesses(): int
    {
        $processes = $this->getTrackedProcesses();
        $killedCount = $this->killProcesses($processes);
        $this->clearTrackedProcesses();

        return $killedCount;
    }

    public function clearTrackedProcesses(): void
    {
        $this->repository->clear();
    }

    private function buildProcessRecord(string $command, int $pid): array
    {
        return [
            'pid' => $pid,
            'command' => $command,
            'started_at' => now()->toIso8601String(),
        ];
    }

    private function filterRunningProcesses(array $processes): array
    {
        return array_filter(
            $processes,
            fn(array $process) => $this->processManager->isRunning($process['pid'])
        );
    }

    private function killProcesses(array $processes): int
    {
        $killedCount = 0;

        foreach ($processes as $process) {
            if ($this->processManager->kill($process['pid'])) {
                $killedCount++;
            }
        }

        return $killedCount;
    }

    public function getProcessCommand(string $command): string
    {
        return $this->commandExtractor->extractCommand($command);
    }
}
