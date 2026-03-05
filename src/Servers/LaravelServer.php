<?php

namespace Igne\LaravelBootstrap\Servers;

use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Illuminate\Console\Command;

final class LaravelServer extends DevServer
{
    public function serve(): int
    {
        $this->displaySectionHeader('🚀 STARTING LARAVEL SERVER');

        return Command::SUCCESS;
    }

    public function postServe(): int
    {
        return parent::postServe();
    }

    public function isAvailableOnSystem(): bool
    {
        return true;
    }

    public function ensureServerInstalled(): void
    {
        // Laravel server has no external dependencies
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function cleanup(): void
    {
        $this->command->stopAllProcesses();
        OSCommand::KILL_PHP_ARTISAN->callSilent();
        $this->info('Laravel server stopped');
    }

    public function getUrl(): string
    {
        return config('app.url');
    }

    public function getServer(): DevServerOption
    {
        return DevServerOption::LARAVEL;
    }

    public function openInBrowser(): void
    {
        $this->browserLauncher->openUrl($this->getUrl());
    }
}
