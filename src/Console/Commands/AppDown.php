<?php

namespace Igne\LaravelBootstrap\Console\Commands;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Development\HerdDevEnvironment;
use Igne\LaravelBootstrap\Development\SailDevEnvironment;
use Igne\LaravelBootstrap\Terminators\BackgroundProcessTerminator;
use Igne\LaravelBootstrap\Handlers\RunnerShutdownHandler;
use Igne\LaravelBootstrap\Confirmations\ShutdownConfirmation;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class AppDown extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:down';

    protected $description = 'Shut down the local Laravel environment (Sail, Herd, etc.) and optionally clean up';

    public function handleWithInterrupts(): int
    {
        $this->displayStartMessage();
        $this->stopBackgroundProcesses();
        $this->shutdownDevEnvironments();
        $this->displayCompletionMessage();

        return Command::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up application environment...');
        $this->externalProcessManager->stopAllProcesses();
        $this->info('Exit completed gracefully.');
    }

    private function displayStartMessage(): void
    {
        $this->info('Stopping application environment...');
        $this->newLine(1);
    }

    private function stopBackgroundProcesses(): void
    {
        $processTerminator = new BackgroundProcessTerminator(
            $this->resolveProcessTracker()
        );

        $processTerminator->stopAll($this->output);
    }

    private function shutdownDevEnvironments(): void
    {
        $this->shutdownDevEnvironment(new HerdDevEnvironment($this), 'Herd');
        $this->shutdownDevEnvironment(new SailDevEnvironment($this), 'Sail');
    }

    private function shutdownDevEnvironment(mixed $environment, string $environmentName): void
    {
        if (!$environment->isRunning()) {
            return;
        }

        $confirmation = new ShutdownConfirmation();
        $shouldStopEnvironment = $confirmation->shouldStopRunner($environmentName);

        $this->displayEnvironmentAction($environmentName, $shouldStopEnvironment);

        $shutdownHandler = new RunnerShutdownHandler($this->externalProcessManager);
        $shutdownHandler->handleShutdown($environment, $shouldStopEnvironment, $environmentName);
    }

    private function displayEnvironmentAction(string $environmentName, bool $shouldStop): void
    {
        if ($shouldStop) {
            $this->info("Stopping {$environmentName}...");
            return;
        }

        $this->info("Stopping processes but keeping {$environmentName} running...");
    }

    private function displayCompletionMessage(): void
    {
        $this->info('Application environment has been stopped.');
    }

    private function resolveProcessTracker(): \Igne\LaravelBootstrap\Managers\ProcessTrackingManager
    {
        return new \Igne\LaravelBootstrap\Managers\ProcessTrackingManager(
            new \Igne\LaravelBootstrap\Repositories\ProcessFileRepository(),
            new \Igne\LaravelBootstrap\Managers\ProcessManager(),
            new \Igne\LaravelBootstrap\Parsers\CommandExtractor()
        );
    }
}
