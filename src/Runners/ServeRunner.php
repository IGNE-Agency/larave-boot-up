<?php

namespace Igne\LaravelBootstrap\Runners;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Services\ToolInstaller;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;

abstract class ServeRunner implements Serve
{
    protected ExternalCommandManager $command;

    public ?InterruptibleCommand $console;

    public function __construct(?InterruptibleCommand $command = null)
    {
        $this->command = new ExternalCommandManager($this->getRunner(), $command?->getOutput());
        $this->console = $command;
        $this->ensureRunnerInstalled();
    }

    abstract public function serve(): int;

    abstract public function isAvailableOnSystem(): bool;

    abstract public function ensureRunnerInstalled(): void;

    abstract public function isRunning(): bool;

    abstract public function cleanup(): void;

    abstract public function getUrl(): string;

    abstract public function getRunner(): ExternalCommandRunner;

    abstract public function openInBrowser(): void;

    public function postServe(): int
    {
        $shouldOpen = config('bootstrap.browser.auto_open', true);

        if ($shouldOpen) {
            $this->openInBrowser();
        }

        $this->console?->info("Done! You can now access your application at {$this->getUrl()}");

        return Command::SUCCESS;
    }

    public function getOutput(): ?OutputStyle
    {
        return $this->console?->getOutput();
    }

    protected function installRunnerIfMissing(string $tool): void
    {
        if ($this->command->isCommandAvailable($tool)) {
            return;
        }

        if (!config('bootstrap.auto_install.enabled', true)) {
            throw new \RuntimeException("{$tool} is not installed. Please install it manually or enable auto_install in config.");
        }

        $this->console?->warn("{$tool} not found. Installing (required for {$this->getRunner()->value} runner)...");

        $installer = new ToolInstaller();
        $installer->setRunner($this->getRunner());
        $installer->install($tool, 'latest', $this->console?->getOutput());

        $this->console?->info("{$tool} installed successfully. Note: You may need to restart your terminal or system for {$tool} to be fully functional.");
    }
}
