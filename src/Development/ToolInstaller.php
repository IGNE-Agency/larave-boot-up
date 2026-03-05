<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Development;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Contracts\InstallsTools;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Symfony\Component\Console\Output\OutputInterface;

final class ToolInstaller implements InstallsTools
{
    private ?DevServerOption $server = null;
    private ExternalCommandManager $commandManager;

    public function __construct()
    {
        $this->commandManager = new ExternalCommandManager;
    }

    public function setServer(?DevServerOption $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function install(string $tool, string $version, ?OutputInterface $output = null): void
    {
        match ($tool) {
            'php' => $this->installPhp($version, $output),
            'bun' => $this->installBun($version, $output),
            'node' => $this->installNode($version, $output),
            'composer' => $this->installComposer($version, $output),
            'yarn' => $this->installYarn($version, $output),
            'npm' => $this->installNpm($version, $output),
            'docker' => $this->installDocker($version, $output),
            'herd' => $this->installHerd($version, $output),
            default => throw new \InvalidArgumentException("Unknown tool: {$tool}")
        };
    }

    public function update(string $tool, string $version, ?OutputInterface $output = null): void
    {
        $this->install($tool, $version, $output);
    }

    protected function installPhp(string $version, ?OutputInterface $output): void
    {
        if ($this->server === DevServerOption::HERD) {
            $phpVersion = $version === 'latest' ? 'php' : $version;
            $this->commandManager->call("herd php:install {$phpVersion} && herd use {$phpVersion}");

            return;
        }

        OSCommand::INSTALL_PHP->forVersion($version)->call();
    }

    protected function installBun(string $version, ?OutputInterface $output): void
    {
        PackageManager::BUN->installPackageManagerCommand($version);
    }

    protected function installNode(string $version, ?OutputInterface $output): void
    {
        OSCommand::INSTALL_NODE->forVersion($version)->call();
    }

    protected function installComposer(string $version, ?OutputInterface $output): void
    {
        OSCommand::INSTALL_COMPOSER->call();
    }

    protected function installYarn(string $version, ?OutputInterface $output): void
    {
        PackageManager::YARN->installPackageManagerCommand($version);
    }

    protected function installNpm(string $version, ?OutputInterface $output): void
    {
        PackageManager::NPM->installPackageManagerCommand($version);
    }

    protected function installDocker(string $version, ?OutputInterface $output): void
    {
        OSCommand::INSTALL_DOCKER->call();
    }

    protected function installHerd(string $version, ?OutputInterface $output): void
    {
        OSCommand::INSTALL_HERD->call();
    }
}
