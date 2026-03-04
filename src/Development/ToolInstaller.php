<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Development;

use Igne\LaravelBootstrap\Contracts\InstallsTools;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Symfony\Component\Console\Output\OutputInterface;

final class ToolInstaller implements InstallsTools
{
    private ?DevServerOption $server = null;
    private ShellCommandRunner $shellRunner;

    public function __construct()
    {
        $this->shellRunner = new ShellCommandRunner();
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
        $command = $this->getPhpInstallCommand($version);
        $this->shellRunner->run($command, $output);
    }

    private function getPhpInstallCommand(string $version): string
    {
        if ($this->server === DevServerOption::HERD) {
            $phpVersion = $version === 'latest' ? 'php' : $version;
            return "herd php:install {$phpVersion} && herd use {$phpVersion}";
        }

        return OSCommand::INSTALL_PHP->forVersion($version)->execute();
    }

    protected function installBun(string $version, ?OutputInterface $output): void
    {
        $command = PackageManager::BUN->installPackageManagerCommand($version);
        $this->shellRunner->run($command, $output);
    }

    protected function installNode(string $version, ?OutputInterface $output): void
    {
        $command = OSCommand::INSTALL_NODE->forVersion($version)->execute();
        $this->shellRunner->run($command, $output);
    }

    protected function installComposer(string $version, ?OutputInterface $output): void
    {
        $command = OSCommand::INSTALL_COMPOSER->execute();
        $this->shellRunner->run($command, $output);
    }

    protected function installYarn(string $version, ?OutputInterface $output): void
    {
        $command = PackageManager::YARN->installPackageManagerCommand($version);
        $this->shellRunner->run($command, $output);
    }

    protected function installNpm(string $version, ?OutputInterface $output): void
    {
        $command = PackageManager::NPM->installPackageManagerCommand($version);
        $this->shellRunner->run($command, $output);
    }

    protected function installDocker(string $version, ?OutputInterface $output): void
    {
        $command = OSCommand::INSTALL_DOCKER->execute();
        $this->shellRunner->run($command, $output);
    }

    protected function installHerd(string $version, ?OutputInterface $output): void
    {
        $command = OSCommand::INSTALL_HERD->execute();
        $this->shellRunner->run($command, $output);
    }

}
