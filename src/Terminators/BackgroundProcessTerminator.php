<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Terminators;

use Igne\LaravelBootstrap\Managers\ProcessTrackingManager;
use Illuminate\Console\OutputStyle;

final class BackgroundProcessTerminator
{
    public function __construct(
        private readonly ProcessTrackingManager $trackingManager
    ) {}

    public function stopAll(OutputStyle $output): void
    {
        $processes = $this->trackingManager->getTrackedProcesses();

        if (empty($processes)) {
            return;
        }

        $this->displayStoppingMessage($output);
        $this->displayProcessList($output, $processes);
        $killedCount = $this->trackingManager->killAllTrackedProcesses();
        $this->displayResult($output, $killedCount);
    }

    private function displayStoppingMessage(OutputStyle $output): void
    {
        $output->info('Stopping background processes...');
    }

    private function displayProcessList(OutputStyle $output, array $processes): void
    {
        foreach ($processes as $process) {
            $commandName = $this->trackingManager->getProcessCommand($process['command']);
            $output->writeln("  - Stopping: {$commandName}");
        }
    }

    private function displayResult(OutputStyle $output, int $killedCount): void
    {
        if ($killedCount > 0) {
            $output->info("✓ Stopped {$killedCount} background process(es)");
        }

        $output->newLine();
    }
}
