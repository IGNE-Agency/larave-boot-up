<?php

namespace Igne\LaravelBootstrap\Runners;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;

final class ServeHerdRunner extends ServeRunner
{
    public function serve(): int
    {
        $herd = ExternalCommandRunner::HERD->command();
        $this->console?->info('Starting Herd development server...');
        $this->command->call("{$herd} link");
        $this->command->call("{$herd} secure");
        $this->command->call("{$herd} start");

        return 0;
    }

    public function postServe(): int
    {
        $herd = ExternalCommandRunner::HERD->command();
        $this->command->call("{$herd} open");
        $pm = $this->command->getPackageManager();
        $this->command->packageManager($pm->devCommand());

        return parent::postServe();
    }

    public function isAvailableOnSystem(): bool
    {
        return $this->command->isCommandAvailable(ExternalCommandRunner::HERD->command());
    }

    public function isRunning(): bool
    {
        $herd = ExternalCommandRunner::HERD->command();

        return $this->command->isCommandRunning("pgrep -f {$herd}");
    }

    public function cleanup(): void
    {
        $herd = ExternalCommandRunner::HERD->command();
        $this->command->stopAllProcesses();
        $this->command->call("{$herd} stop");
        $this->console?->info('Herd server stopped');
    }

    public function getUrl(): string
    {
        return config('app.url');
    }

    public function getRunner(): ExternalCommandRunner
    {
        return ExternalCommandRunner::HERD;
    }
}
