<?php

namespace Igne\LaravelBootstrap\Runners;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Illuminate\Console\OutputStyle;

abstract class ServeRunner implements Serve
{
    protected ExternalCommandManager $command;

    protected ?InterruptibleCommand $console;

    public function __construct(?InterruptibleCommand $command = null)
    {
        $this->command = new ExternalCommandManager($this->getRunner(), $command?->getOutput());
        $this->console = $command;
        if (!$this->isAvailableOnSystem()) {
            throw new \RuntimeException("The command {$this->getRunner()->command()} is not available on the system. Please install it with all its dependencies.");
        }
    }

    abstract public function serve(): int;

    abstract public function isAvailableOnSystem(): bool;

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

        return 0;
    }

    public function getOutput(): ?OutputStyle
    {
        return $this->console->getOutput();
    }
}
