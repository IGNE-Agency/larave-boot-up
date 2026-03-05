<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Launchers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Igne\LaravelBootstrap\Traits\HasOutputMethods;
use Illuminate\Console\OutputStyle;

final class BrowserLauncher
{
    use HasOutputMethods;

    public function __construct(
        private readonly ExternalCommandManager $commandManager,
        private readonly ?OutputStyle $output = null
    ) {}

    protected function getOutputHandler(): mixed
    {
        return $this->output;
    }

    public function openUrl(string $url): void
    {
        if (! $this->canOpenBrowser()) {
            $this->displayManualOpenMessage($url);

            return;
        }

        $this->executeBrowserCommand($url);
    }

    public function openWithCommand(string $command): void
    {
        if (! OSCommand::OPEN_BROWSER->canExecute()) {
            return;
        }

        $this->commandManager->call($command);
    }

    private function canOpenBrowser(): bool
    {
        return OSCommand::OPEN_BROWSER->canExecute();
    }

    private function executeBrowserCommand(string $url): void
    {
        $command = OSCommand::OPEN_BROWSER->forUrl($url)->execute();
        $this->commandManager->callSilent($command);
    }

    private function displayManualOpenMessage(string $url): void
    {
        $this->comment("No browser detected. Please open {$url} manually.");
    }
}
