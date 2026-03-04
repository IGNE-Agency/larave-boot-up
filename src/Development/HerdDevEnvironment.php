<?php

namespace Igne\LaravelBootstrap\Development;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Illuminate\Console\Command;

final class HerdDevEnvironment extends DevEnvironmentRunner
{
    public function serve(): int
    {
        $herd = ExternalCommandRunner::HERD->command();

        $this->displaySectionHeader('⚡ SETTING UP HERD');

        $this->command->callSilent("{$herd} link");
        $this->info('✓ Project linked to Herd');

        $this->command->callSilent("{$herd} secure");
        $this->info('✓ HTTPS certificate configured');

        return Command::SUCCESS;
    }

    public function postServe(): int
    {
        return parent::postServe();
    }

    public function isAvailableOnSystem(): bool
    {
        return $this->command->isCommandAvailable(ExternalCommandRunner::HERD->command());
    }

    public function ensureRunnerInstalled(): void
    {
        $this->installRunnerIfMissing('herd');
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
        $this->info('Herd server stopped');
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
        $this->browserLauncher->openWithCommand("{$herd} open");
    }
}
