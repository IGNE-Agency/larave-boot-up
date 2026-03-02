<?php

namespace Igne\LaravelBootstrap\Runners;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\OSCommand;

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
        $checkCommand = OSCommand::CHECK_PROCESS->forProcess($herd)->execute();

        return $this->command->isCommandRunning($checkCommand);
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

    public function openInBrowser(): void
    {
        $herd = ExternalCommandRunner::HERD->command();

        if (OSCommand::OPEN_BROWSER->canExecute()) {
            $this->command->callSilent("{$herd} open");
        } else {
            $this->console?->warn('No browser detected. Please open ' . $this->getUrl() . ' manually.');
        }
    }
}
