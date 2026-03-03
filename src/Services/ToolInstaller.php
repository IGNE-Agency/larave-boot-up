<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Services;

use Igne\LaravelBootstrap\Contracts\InstallsTools;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class ToolInstaller implements InstallsTools
{
    private ?ExternalCommandRunner $runner = null;

    public function setRunner(?ExternalCommandRunner $runner): self
    {
        $this->runner = $runner;
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
        if ($this->runner === ExternalCommandRunner::HERD) {
            $this->installPhpWithHerd($version, $output);
            return;
        }

        $command = OSCommand::INSTALL_PHP->forVersion($version)->execute();
        $this->runCommand($command, $output);
    }

    protected function installPhpWithHerd(string $version, ?OutputInterface $output): void
    {
        $phpVersion = $version === 'latest' ? 'php' : $version;
        $command = "herd php:install {$phpVersion} && herd use {$phpVersion}";
        $this->runCommand($command, $output);
    }

    protected function installBun(string $version, ?OutputInterface $output): void
    {
        $command = PackageManager::BUN->installPackageManagerCommand($version);
        $this->runCommand($command, $output);
    }

    protected function installNode(string $version, ?OutputInterface $output): void
    {
        $command = OSCommand::INSTALL_NODE->forVersion($version)->execute();
        $this->runCommand($command, $output);
    }

    protected function installComposer(string $version, ?OutputInterface $output): void
    {
        $command = OSCommand::INSTALL_COMPOSER->execute();
        $this->runCommand($command, $output);
    }

    protected function installYarn(string $version, ?OutputInterface $output): void
    {
        $command = PackageManager::YARN->installPackageManagerCommand($version);
        $this->runCommand($command, $output);
    }

    protected function installNpm(string $version, ?OutputInterface $output): void
    {
        $command = PackageManager::NPM->installPackageManagerCommand($version);
        $this->runCommand($command, $output);
    }

    protected function installDocker(string $version, ?OutputInterface $output): void
    {
        $command = OSCommand::INSTALL_DOCKER->execute();
        $this->runCommand($command, $output);
    }

    protected function installHerd(string $version, ?OutputInterface $output): void
    {
        $command = OSCommand::INSTALL_HERD->execute();
        $this->runCommand($command, $output);
    }

    protected function runCommand(string $command, ?OutputInterface $output): void
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300);

        $process->run(function ($type, $buffer) use ($output) {
            if ($output) {
                $output->write($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Failed to run command: {$command}");
        }
    }
}
