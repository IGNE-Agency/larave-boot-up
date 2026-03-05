<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Builders;

use Igne\LaravelBootstrap\Enums\OSCommand;
use Illuminate\Support\Facades\Artisan;

final class OSCommandBuilder
{
    private ?string $command = null;
    private ?string $process = null;
    private ?int $pid = null;
    private ?string $version = null;
    private ?string $url = null;
    private bool $silent = false;

    public function __construct(
        private readonly OSCommand $osCommand
    ) {}

    // ==================== Builder Methods ====================

    public function withCommand(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function forProcess(string $process): self
    {
        $this->process = $process;

        return $this;
    }

    public function forPid(int $pid): self
    {
        $this->pid = $pid;

        return $this;
    }

    public function forVersion(string $version = 'latest'): self
    {
        $this->version = $version;

        return $this;
    }

    public function forUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function silent(): self
    {
        $this->silent = true;

        return $this;
    }

    public function call(): int
    {
        $outputBuffer = $this->silent ? new \Symfony\Component\Console\Output\NullOutput : null;

        return match ($this->osCommand) {
            OSCommand::KILL_PHP_ARTISAN => Artisan::call('os:kill-php-artisan', [], $outputBuffer),
            OSCommand::CHECK_PROCESS => Artisan::call('os:check-process', ['process' => $this->getProcess()], $outputBuffer),
            OSCommand::KILL_PROCESS => Artisan::call('os:kill-process', ['pid' => $this->getPid()], $outputBuffer),
            OSCommand::OPEN_TERMINAL => Artisan::call('os:open-terminal', ['command' => $this->getCommand()], $outputBuffer),
            OSCommand::OPEN_BROWSER => Artisan::call('os:open-browser', ['url' => $this->getUrl()], $outputBuffer),
            OSCommand::START_DOCKER => Artisan::call('os:start-docker', [], $outputBuffer),
            OSCommand::INSTALL_PHP => Artisan::call('os:install-php', ['version' => $this->getVersion()], $outputBuffer),
            OSCommand::INSTALL_NODE => Artisan::call('os:install-node', ['version' => $this->getVersion()], $outputBuffer),
            OSCommand::INSTALL_COMPOSER => Artisan::call('os:install-composer', [], $outputBuffer),
            OSCommand::INSTALL_BUN => Artisan::call('os:install-bun', ['version' => $this->getVersion()], $outputBuffer),
            OSCommand::INSTALL_YARN => Artisan::call('os:install-yarn', ['version' => $this->getVersion()], $outputBuffer),
            OSCommand::INSTALL_NPM => Artisan::call('os:install-npm', ['version' => $this->getVersion()], $outputBuffer),
            OSCommand::INSTALL_HOMEBREW => Artisan::call('os:install-homebrew', [], $outputBuffer),
            OSCommand::INSTALL_DOCKER => Artisan::call('os:install-docker', [], $outputBuffer),
            OSCommand::INSTALL_HERD => Artisan::call('os:install-herd', [], $outputBuffer),
        };
    }

    public function callSilent(): int
    {
        return $this->silent()->call();
    }

    // ==================== Getters & Helpers ====================

    private function getProcess(): string
    {
        return $this->process ?? '';
    }

    private function getCommand(): string
    {
        return $this->command ?? 'php artisan';
    }

    private function getVersion(): string
    {
        return $this->version ?? 'latest';
    }

    private function getUrl(): string
    {
        return $this->url ?? '';
    }

    private function getPid(): int
    {
        return $this->pid ?? 0;
    }

    private function isLatestVersion(string $version): bool
    {
        return $version === 'latest';
    }
}
