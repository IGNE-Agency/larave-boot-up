<?php

namespace Igne\LaravelBootstrap\Development;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Illuminate\Console\Command;

final class LaravelDevEnvironment extends DevEnvironmentRunner
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

    public function ensureRunnerInstalled(): void
    {
        // Laravel runner has no external dependencies
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function cleanup(): void
    {
        $this->command->stopAllProcesses();
        $this->command->callSilent(OSCommand::KILL_PHP_ARTISAN->execute());
        $this->info('Laravel server stopped');
    }

    public function getUrl(): string
    {
        return config('app.url');
    }

    public function getRunner(): ExternalCommandRunner
    {
        return ExternalCommandRunner::LARAVEL;
    }

    public function openInBrowser(): void
    {
        $this->browserLauncher->openUrl($this->getUrl());
    }
}
