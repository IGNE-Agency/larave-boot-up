<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Bootstrap;

use Igne\LaravelBootstrap\Exceptions\ServeException;
use Igne\LaravelBootstrap\Managers\DockerManager;
use Igne\LaravelBootstrap\Managers\SailManager;
use Igne\LaravelBootstrap\Strategies\PollingStrategy;
use Igne\LaravelBootstrap\Traits\HasOutputMethods;
use Illuminate\Console\OutputStyle;

use function Laravel\Prompts\spin;

final class SailBootstrap
{
    use HasOutputMethods;

    public function __construct(
        private readonly SailManager $sailManager,
        private readonly DockerManager $dockerManager,
        private readonly PollingStrategy $pollingStrategy,
        private readonly ?OutputStyle $output = null
    ) {}

    protected function getOutputHandler(): mixed
    {
        return $this->output;
    }

    public function ensureInstalled(): void
    {
        if ($this->sailManager->isInstalled()) {
            return;
        }

        $this->sailManager->install();
        $this->info('Sail installed successfully.');
    }

    public function ensureConfigured(): void
    {
        if ($this->sailManager->isConfigured()) {
            return;
        }

        $this->sailManager->configure();
        $this->info('Sail configuration updated successfully.');
    }

    public function ensureDockerRunning(?callable $onInterrupt = null): void
    {
        if ($this->dockerManager->isRunning()) {
            $this->info('Docker containers are running.');

            return;
        }

        $this->info('Starting Docker containers...');
        $this->dockerManager->start();
        $this->waitForDocker($onInterrupt);
    }

    public function ensureSailRunning(?callable $onInterrupt = null): void
    {
        if ($this->sailManager->isRunning()) {
            $this->info('Sail containers are running.');

            return;
        }

        $this->info('Starting Sail containers...');
        $this->sailManager->start();
        $this->waitForSail($onInterrupt);
    }

    private function waitForDocker(?callable $onInterrupt): void
    {
        try {
            spin(
                callback: function () use ($onInterrupt) {
                    $this->pollingStrategy->waitFor(
                        fn () => $this->dockerManager->isRunning(),
                        fn () => $this->info('Docker containers started successfully.'),
                        fn ($timeoutSeconds) => throw new ServeException("Docker failed to start in time. Waited for {$timeoutSeconds} seconds."),
                        onInterrupt: fn () => throw new ServeException('Docker startup interrupted.'),
                        isInterrupted: $onInterrupt
                    );
                },
                message: 'Waiting for Docker to start...'
            );
        } catch (ServeException $e) {
            $this->error($e->getMessage());
            throw $e;
        }
    }

    private function waitForSail(?callable $onInterrupt): void
    {
        $timeoutSeconds = 600;

        try {
            spin(
                callback: function () use ($timeoutSeconds, $onInterrupt) {
                    $this->pollingStrategy->waitFor(
                        fn () => $this->sailManager->isRunning(),
                        fn () => $this->info('Sail started successfully.'),
                        fn ($timeoutSeconds) => throw new ServeException("Sail failed to start in time. Waited for {$timeoutSeconds} seconds."),
                        fn () => null,
                        $timeoutSeconds,
                        1000,
                        fn () => throw new ServeException('Sail startup interrupted.'),
                        $onInterrupt
                    );
                },
                message: 'Waiting for Sail to start...'
            );
        } catch (ServeException $e) {
            $this->error($e->getMessage());
            throw $e;
        }
    }
}
