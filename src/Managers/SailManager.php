<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Managers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\OutputInterface;

final class SailManager
{
    public function __construct(
        private readonly ExternalCommandManager $commandManager,
        private readonly ?OutputInterface $output = null
    ) {
    }

    public function isRunning(): bool
    {
        $sail = DevServerOption::SAIL->command();
        return $this->commandManager->isCommandRunning("{$sail} ps -q");
    }

    public function start(): void
    {
        $sail = DevServerOption::SAIL->command();
        $this->commandManager->call("{$sail} up -d");
    }

    public function isInstalled(): bool
    {
        return $this->hasDockerCompose() && $this->hasDevContainer();
    }

    public function install(): void
    {
        Artisan::call('sail:install', [
            '--with' => 'mysql',
            '--devcontainer' => true,
        ], $this->output);
    }

    public function isConfigured(): bool
    {
        return $this->doesEnvMatchConfig();
    }

    public function configure(): void
    {
        Artisan::call('sail:build', [
            '--no-cache' => true,
        ], $this->output);
    }

    private function hasDockerCompose(): bool
    {
        return File::exists(base_path('docker-compose.yml'));
    }

    private function hasDevContainer(): bool
    {
        return File::exists(base_path('.devcontainer/devcontainer.json'));
    }

    private function doesEnvMatchConfig(): bool
    {
        return true;
    }
}
