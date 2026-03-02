<?php

namespace Igne\LaravelBootstrap\Runners;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Exceptions\ServeException;
use Artisan;

final class ServeSailRunner extends ServeRunner
{
    public function serve(): int
    {
        $this->console?->info('Starting Sail development server...');
        $this->installSail()
            ->bootDocker()
            ->bootSail();

        return 0;
    }

    public function postServe(): int
    {
        $pm = $this->command->getPackageManager();
        $this->command->packageManager($pm->devCommand());

        return parent::postServe();
    }

    public function isAvailableOnSystem(): bool
    {
        return
            file_exists(base_path(ExternalCommandRunner::SAIL->command()))
            && $this->command->isCommandAvailable('docker');

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
        $this->command->call('open -a Docker');
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
        $this->console?->info('Waiting for Docker to start...');
        $this->command->waitFor(
            fn() => $this->isDockerRunning(),
            fn() => $this->console?->info('Docker containers started successfully.'),
            fn($timeoutSeconds) => throw new ServeException("Docker failed to start in time. Waited for {$timeoutSeconds} seconds."),
            onInterrupt: fn() => $this->console?->info('Docker startup interrupted.'),
            isInterrupted: $this->console?->trap(
                [SIGINT, SIGTERM],
                function ($signal) {
                    $this->cleanup();
                    exit(0);
                }
            )
        );

        return $this;
    }

    protected function waitForSail(): self
    {
        $this->console?->info('Waiting for Sail to start...');
        $timeoutSeconds = 600;
        $this->console?->getOutput()->createProgressBar($timeoutSeconds);
        $this->console?->getOutput()->progressStart();
        $this->command->waitFor(
            fn() => $this->isSailRunning(),
            function () {
                $this->console?->info('Sail started successfully.');
                $this->console?->getOutput()->progressFinish();
            },
            function ($timeoutSeconds) {
                $this->console?->getOutput()->progressFinish();
                throw new ServeException("Sail failed to start in time. Waited for {$timeoutSeconds} seconds.");
            },
            fn() => $this->console?->getOutput()->progressAdvance(),
            $timeoutSeconds,
            1000,
            function () {
                $this->console?->info(string: 'Sail startup interrupted.');
                $this->console?->getOutput()->progressFinish();
            },
            $this->console?->trap(
                [SIGINT, SIGTERM],
                function ($signal) {
                    $this->cleanup();
                    exit(0);
                }
            )
        );

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
        if (!file_exists(base_path('docker-compose.yml'))) {
            $this->console?->info('docker-compose.yml not found, installing Sail...');

            return false;
        }

        if (!file_exists(base_path('.devcontainer/devcontainer.json'))) {
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
