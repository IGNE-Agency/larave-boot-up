<?php

namespace Igne\LaravelBootstrap\Runners;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Illuminate\Console\Command;

final class ServeLaravelRunner extends ServeRunner
{
    public function serve(): int
    {
        $this->console?->info('Starting Laravel development server...');

        return Command::SUCCESS;
    }

    public function postServe(): int
    {
        $pm = $this->command->getPackageManager();
        $this->command->packageManager($pm->devCommand());

        return parent::postServe();
    }

    public function isAvailableOnSystem(): bool
    {
        return true;
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function cleanup(): void
    {
        $this->command->stopAllProcesses();
        $this->command->callSilent(OSCommand::KILL_PHP_ARTISAN->execute());
        $this->console?->info('Laravel server stopped');
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
        if (OSCommand::OPEN_BROWSER->canExecute()) {
            $url = $this->getUrl();
            $this->command->callSilent(OSCommand::OPEN_BROWSER->forUrl($url)->execute());
        } else {
            $this->console?->warn('No browser detected. Please open ' . $this->getUrl() . ' manually.');
        }
    }
}
