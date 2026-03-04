<?php

namespace Igne\LaravelBootstrap\Enums;

use Illuminate\Support\Facades\File;

enum OSCommand: string
{
    case KILL_PHP_ARTISAN = 'kill_php_artisan';
    case CHECK_PROCESS = 'check_process';
    case KILL_PROCESS = 'kill_process';
    case OPEN_TERMINAL = 'open_terminal';
    case START_DOCKER = 'start_docker';
    case INSTALL_PHP = 'install_php';
    case INSTALL_BUN = 'install_bun';
    case INSTALL_NODE = 'install_node';
    case INSTALL_COMPOSER = 'install_composer';
    case INSTALL_YARN = 'install_yarn';
    case INSTALL_NPM = 'install_npm';
    case INSTALL_HOMEBREW = 'install_homebrew';
    case INSTALL_DOCKER = 'install_docker';
    case INSTALL_HERD = 'install_herd';
    case OPEN_BROWSER = 'open_browser';

    public function forProcess(string $process): OSCommandBuilder
    {
        return (new OSCommandBuilder($this))->forProcess($process);
    }

    public function forPid(int $pid): OSCommandBuilder
    {
        return (new OSCommandBuilder($this))->forPid($pid);
    }

    public function withCommand(string $command): OSCommandBuilder
    {
        return (new OSCommandBuilder($this))->withCommand($command);
    }

    public function forVersion(string $version = 'latest'): OSCommandBuilder
    {
        return (new OSCommandBuilder($this))->forVersion($version);
    }

    public function forUrl(string $url): OSCommandBuilder
    {
        return (new OSCommandBuilder($this))->forUrl($url);
    }

    public function execute(): string
    {
        return (new OSCommandBuilder($this))->execute();
    }

    public function builder(): OSCommandBuilder
    {
        return new OSCommandBuilder($this);
    }

    public function canExecute(): bool
    {
        return match ($this) {
            self::OPEN_TERMINAL => $this->canOpenTerminal(),
            self::OPEN_BROWSER => $this->canOpenBrowser(),
            default => true,
        };
    }

    private function canOpenTerminal(): bool
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => true,
            'Linux' => File::exists('/usr/bin/gnome-terminal') || File::exists('/usr/bin/xterm'),
            'Windows' => true,
            default => false,
        };
    }

    private function canOpenBrowser(): bool
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => true,
            'Windows' => true,
            'Linux' => File::exists('/usr/bin/xdg-open') || File::exists('/usr/bin/sensible-browser') || File::exists('/usr/bin/x-www-browser'),
            default => false,
        };
    }
}
