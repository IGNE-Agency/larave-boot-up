<?php

namespace Igne\LaravelBootstrap\Servers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Server;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Launchers\BrowserLauncher;
use Igne\LaravelBootstrap\Managers\ToolInstallationManager;
use Igne\LaravelBootstrap\Resolvers\ConfigResolver;
use Igne\LaravelBootstrap\Traits\HasOutputMethods;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;

abstract class DevServer implements Server
{
    use HasOutputMethods;
    protected ExternalCommandManager $command;
    protected BrowserLauncher $browserLauncher;
    protected ConfigResolver $configResolver;
    protected ToolInstallationManager $installationManager;
    public ?InterruptibleCommand $console;

    public function __construct(?InterruptibleCommand $command = null)
    {
        $this->command = new ExternalCommandManager($this->getServer(), $command?->getOutput());
        $this->console = $command;
        $this->configResolver = new ConfigResolver();
        $this->browserLauncher = new BrowserLauncher($this->command, $command?->getOutput());
        $this->installationManager = new ToolInstallationManager($this->command, $this->configResolver, $command?->getOutput());
        $this->ensureServerInstalled();
    }

    abstract public function serve(): int;

    abstract public function isAvailableOnSystem(): bool;

    abstract public function ensureServerInstalled(): void;

    abstract public function isRunning(): bool;

    abstract public function cleanup(): void;

    abstract public function getUrl(): string;

    abstract public function getServer(): DevServerOption;

    abstract public function openInBrowser(): void;

    public function postServe(): int
    {
        if ($this->configResolver->shouldAutoOpenBrowser()) {
            $this->openInBrowser();
        }

        $this->displaySuccessMessage();

        return Command::SUCCESS;
    }

    public function getOutput(): ?OutputStyle
    {
        return $this->console?->getOutput();
    }

    protected function getOutputHandler(): mixed
    {
        return $this->console;
    }

    protected function installServerIfMissing(string $tool): void
    {
        $this->installationManager->ensureInstalled($tool, $this->getServer());
    }

    private function displaySuccessMessage(): void
    {
        $this->info("Done! You can now access your application at {$this->getUrl()}");
    }
}
