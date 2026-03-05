<?php

namespace Igne\LaravelBootstrap\Console\Commands;

use Igne\LaravelBootstrap\Confirmations\ShutdownConfirmation;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Handlers\ServerShutdownHandler;
use Igne\LaravelBootstrap\Servers\HerdServer;
use Igne\LaravelBootstrap\Servers\SailServer;
use Igne\LaravelBootstrap\Terminators\BackgroundProcessTerminator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class AppDown extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:down';

    protected $description = 'Shut down the development server (Sail, Herd, etc.) and clean up';

    public function handleWithInterrupts(): int
    {
        $this->displayStartMessage();
        $this->stopBackgroundProcesses();
        $this->shutdownDevServers();
        $this->displayCompletionMessage();

        return Command::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up development server...');
        $this->externalProcessManager->stopAllProcesses();
        $this->info('Exit completed gracefully.');
    }

    private function displayStartMessage(): void
    {
        $this->info('Stopping development server...');
        $this->newLine(1);
    }

    private function stopBackgroundProcesses(): void
    {
        $processTerminator = new BackgroundProcessTerminator(
            $this->resolveProcessTracker()
        );

        $processTerminator->stopAll($this->output);
    }

    private function shutdownDevServers(): void
    {
        $this->shutdownDevServer(new HerdServer($this), 'Herd');
        $this->shutdownDevServer(new SailServer($this), 'Sail');
    }

    private function shutdownDevServer(mixed $server, string $serverName): void
    {
        if (! $server->isRunning()) {
            return;
        }

        $confirmation = new ShutdownConfirmation;
        $shouldStopServer = $confirmation->shouldStopServer($serverName);

        $this->displayServerAction($serverName, $shouldStopServer);

        $shutdownHandler = new ServerShutdownHandler($this->externalProcessManager);
        $shutdownHandler->handleShutdown($server, $shouldStopServer, $serverName);
    }

    private function displayServerAction(string $serverName, bool $shouldStop): void
    {
        if ($shouldStop) {
            $this->info("Stopping {$serverName}...");

            return;
        }

        $this->info("Stopping processes but keeping {$serverName} running...");
    }

    private function displayCompletionMessage(): void
    {
        $this->info('Development server has been stopped.');
    }

    private function resolveProcessTracker(): \Igne\LaravelBootstrap\Managers\ProcessTrackingManager
    {
        return new \Igne\LaravelBootstrap\Managers\ProcessTrackingManager(
            new \Igne\LaravelBootstrap\Repositories\ProcessFileRepository,
            new \Igne\LaravelBootstrap\Managers\ProcessManager,
            new \Igne\LaravelBootstrap\Parsers\CommandExtractor
        );
    }
}
