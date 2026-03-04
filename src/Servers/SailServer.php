<?php

namespace Igne\LaravelBootstrap\Servers;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Bootstrap\SailBootstrap;
use Igne\LaravelBootstrap\Managers\DockerManager;
use Igne\LaravelBootstrap\Managers\SailManager;
use Igne\LaravelBootstrap\Strategies\PollingStrategy;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

final class SailServer extends DevServer
{
    private SailBootstrap $sailBootstrap;
    private SailManager $sailManager;
    private DockerManager $dockerManager;

    public function __construct(?InterruptibleCommand $command = null)
    {
        parent::__construct($command);
        $this->sailManager = new SailManager($this->command, $command?->getOutput());
        $this->dockerManager = new DockerManager($this->command);
        $this->sailBootstrap = new SailBootstrap(
            $this->sailManager,
            $this->dockerManager,
            new PollingStrategy(),
            $command?->getOutput()
        );
    }

    public function serve(): int
    {
        $this->displaySectionHeader('⛵ SETTING UP SAIL');

        $this->sailBootstrap->ensureInstalled();
        $this->sailBootstrap->ensureConfigured();
        $this->sailBootstrap->ensureDockerRunning($this->getInterruptHandler());
        $this->sailBootstrap->ensureSailRunning($this->getInterruptHandler());

        return Command::SUCCESS;
    }

    public function postServe(): int
    {
        return parent::postServe();
    }

    public function isAvailableOnSystem(): bool
    {
        return
            File::exists(base_path(DevServerOption::SAIL->command()))
            && $this->command->isCommandAvailable('docker');

    }

    public function ensureServerInstalled(): void
    {
        $this->installServerIfMissing('docker');
    }

    public function isRunning(): bool
    {
        return $this->sailManager->isRunning() || $this->dockerManager->isRunning();
    }

    public function cleanup(): void
    {
        $sail = DevServerOption::SAIL->command();
        $this->command->stopAllProcesses();
        $this->command->call("{$sail} down");
        $this->info('Sail server stopped');
    }

    public function getUrl(): string
    {
        return 'http://localhost';
    }

    public function getServer(): DevServerOption
    {
        return DevServerOption::SAIL;
    }

    public function openInBrowser(): void
    {
        $this->browserLauncher->openUrl($this->getUrl());
    }

    private function getInterruptHandler(): ?callable
    {
        return $this->console?->trap(
            [SIGINT, SIGTERM],
            function ($signal) {
                $this->cleanup();
                exit(Command::SUCCESS);
            }
        );
    }
}
