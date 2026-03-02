<?php

namespace Igne\LaravelBootstrap\Runners;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;

final class ServeLaravelRunner extends ServeRunner
{
    public function serve(): int
    {
        $this->console?->info('Starting Laravel development server...');

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
        return true;
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function cleanup(): void
    {
        $this->command->stopAllProcesses();
        $this->command->call('pkill -f "php artisan serve"');
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
}
