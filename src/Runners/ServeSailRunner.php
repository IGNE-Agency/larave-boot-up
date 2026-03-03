<?php

namespace Igne\LaravelBootstrap\Runners;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Igne\LaravelBootstrap\Exceptions\ServeException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

use function Laravel\Prompts\spin;

final class ServeSailRunner extends ServeRunner
{
    public function serve(): int
    {
        $this->console?->info('Starting Sail development server...');
        $this->installSail()
            ->bootDocker()
            ->bootSail();

        return Command::SUCCESS;
    }

    public function postServe(): int
    {
        return parent::postServe();
    }

    public function isAvailableOnSystem(): bool
    {
        return
            File::exists(base_path(ExternalCommandRunner::SAIL->command()))
            && $this->command->isCommandAvailable('docker');

    }

    public function ensureRunnerInstalled(): void
    {
        $this->installRunnerIfMissing('docker');
    }

    public function isRunning(): bool
    {
        return $this->isSailRunning() || $this->isDockerRunning();
    }

    public function cleanup(): void
    {
        $sail = ExternalCommandRunner::SAIL->command();
        $this->command->stopAllProcesses();
        $this->command->call("{$sail} down");
        $this->console?->info('Sail server stopped');
    }

    public function getUrl(): string
    {
        return 'http://localhost';
    }

    public function getRunner(): ExternalCommandRunner
    {
        return ExternalCommandRunner::SAIL;
    }

    public function openInBrowser(): void
    {
        if (OSCommand::OPEN_BROWSER->canExecute()) {
            $url = $this->getUrl();
            $this->command->callSilent(OSCommand::OPEN_BROWSER->forUrl($url)->execute());
        } else {
            $this->console?->warn('No browser detected. Please open ' . $this->getUrl() . ' manually.');
        }
    }

    protected function installSail(): self
    {
        if (!$this->isSailCorrectlyInstalled()) {
            Artisan::call('sail:install', [
                '--with' => 'mysql',
                '--devcontainer' => true,
            ], $this->console?->getOutput());
            $this->console?->info('Sail installed successfully.');
        }

        if (!$this->isSailCorrectlyConfigured()) {
            Artisan::call('sail:build', [
                '--no-cache' => true,
            ], $this->console?->getOutput());
            $this->console?->info('Sail configuration updated successfully.');
        }

        return $this;
    }

    protected function bootDocker(): self
    {
        if ($this->isDockerRunning()) {
            $this->console?->info('Docker containers are running.');

            return $this;
        }

        $this->console?->info('Starting Docker containers...');
        $this->command->call(OSCommand::START_DOCKER->execute());
        $this->waitForDocker();

        return $this;
    }

    protected function bootSail(): self
    {
        if ($this->isSailRunning()) {
            $this->console?->info('Sail containers are running.');

            return $this;
        }

        $sail = ExternalCommandRunner::SAIL->command();
        $this->console?->info('Starting Sail containers...');
        $this->command->call("{$sail} up -d");
        $this->waitForSail();

        return $this;
    }

    protected function waitForDocker(): self
    {
        try {
            spin(
                callback: function () {
                    $this->command->waitFor(
                        fn() => $this->isDockerRunning(),
                        fn() => $this->console?->info('Docker containers started successfully.'),
                        fn($timeoutSeconds) => throw new ServeException("Docker failed to start in time. Waited for {$timeoutSeconds} seconds."),
                        onInterrupt: fn() => throw new ServeException('Docker startup interrupted.'),
                        isInterrupted: $this->console?->trap(
                            [SIGINT, SIGTERM],
                            function ($signal) {
                                $this->cleanup();
                                exit(Command::SUCCESS);
                            }
                        )
                    );
                },
                message: 'Waiting for Docker to start...'
            );
        } catch (ServeException $e) {
            $this->console?->error($e->getMessage());
            throw $e;
        }

        return $this;
    }

    protected function waitForSail(): self
    {
        $timeoutSeconds = 600;

        try {
            spin(
                callback: function () use ($timeoutSeconds) {
                    $this->command->waitFor(
                        fn() => $this->isSailRunning(),
                        fn() => $this->console?->info('Sail started successfully.'),
                        fn($timeoutSeconds) => throw new ServeException("Sail failed to start in time. Waited for {$timeoutSeconds} seconds."),
                        fn() => null,
                        $timeoutSeconds,
                        1000,
                        fn() => throw new ServeException('Sail startup interrupted.'),
                        $this->console?->trap(
                            [SIGINT, SIGTERM],
                            function ($signal) {
                                $this->cleanup();
                                exit(Command::SUCCESS);
                            }
                        )
                    );
                },
                message: 'Waiting for Sail to start...'
            );
        } catch (ServeException $e) {
            $this->console?->error($e->getMessage());
            throw $e;
        }

        return $this;
    }

    private function isSailRunning(): bool
    {
        $sail = ExternalCommandRunner::SAIL->command();

        return $this->command->isCommandRunning("{$sail} ps -q");
    }

    protected function isDockerRunning(): bool
    {
        return $this->command->isCommandRunning('docker info');
    }

    protected function isSailCorrectlyInstalled(): bool
    {
        if (!File::exists(base_path('docker-compose.yml'))) {
            $this->console?->info('docker-compose.yml not found, installing Sail...');

            return false;
        }

        if (!File::exists(base_path('.devcontainer/devcontainer.json'))) {
            $this->console?->info('.devcontainer/devcontainer.json not found, installing Sail...');

            return false;
        }

        return true;
    }

    protected function isSailCorrectlyConfigured(): bool
    {
        if (!$this->doesEnvMatchConfig()) {
            $this->console?->info('.env file does not match Sail configuration, rebuilding Sail...');

            return false;
        }

        return true;
    }

    protected function doesEnvMatchConfig(): bool
    {
        return true;
    }
}
