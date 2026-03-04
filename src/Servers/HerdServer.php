<?php

namespace Igne\LaravelBootstrap\Servers;

use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Illuminate\Console\Command;

final class HerdServer extends DevServer
{
    public function serve(): int
    {
        $herd = DevServerOption::HERD->command();

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
        return $this->command->isCommandAvailable(DevServerOption::HERD->command());
    }

    public function ensureServerInstalled(): void
    {
        $this->installServerIfMissing('herd');
    }

    public function isRunning(): bool
    {
        $herd = DevServerOption::HERD->command();
        $checkCommand = OSCommand::CHECK_PROCESS->forProcess($herd)->execute();

        return $this->command->isCommandRunning($checkCommand);
    }

    public function cleanup(): void
    {
        $herd = DevServerOption::HERD->command();
        $this->command->stopAllProcesses();
        $this->command->call("{$herd} stop");
        $this->info('Herd server stopped');
    }

    public function getUrl(): string
    {
        return config('app.url');
    }

    public function getServer(): DevServerOption
    {
        return DevServerOption::HERD;
    }

    public function openInBrowser(): void
    {
        $herd = DevServerOption::HERD->command();
        $this->browserLauncher->openWithCommand("{$herd} open");
    }
}
