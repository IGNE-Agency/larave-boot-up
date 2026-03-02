<?php

namespace Igne\LaravelBootstrap\Services;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class ToolInstaller
{
    public function install(string $tool, string $version, ?OutputInterface $output = null): void
    {
        match ($tool) {
            'bun' => $this->installBun($version, $output),
            'node' => $this->installNode($version, $output),
            'composer' => $this->installComposer($version, $output),
            'yarn' => $this->installYarn($version, $output),
            'npm' => $this->installNpm($version, $output),
            default => throw new \InvalidArgumentException("Unknown tool: {$tool}")
        };
    }

    public function update(string $tool, string $version, ?OutputInterface $output = null): void
    {
        $this->install($tool, $version, $output);
    }

    protected function installBun(string $version, ?OutputInterface $output): void
    {
        $command = $version === 'latest'
            ? 'curl -fsSL https://bun.sh/install | bash'
            : "curl -fsSL https://bun.sh/install | bash -s \"bun-v{$version}\"";

        $this->runCommand($command, $output);
    }

    protected function installNode(string $version, ?OutputInterface $output): void
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            $command = $version === 'latest'
                ? 'brew install node'
                : "brew install node@{$version}";
        } else {
            $command = $version === 'latest'
                ? 'curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash - && sudo apt-get install -y nodejs'
                : "curl -fsSL https://deb.nodesource.com/setup_{$version}.x | sudo -E bash - && sudo apt-get install -y nodejs";
        }

        $this->runCommand($command, $output);
    }

    protected function installComposer(string $version, ?OutputInterface $output): void
    {
        $command = 'curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer';
        
        $this->runCommand($command, $output);
    }

    protected function installYarn(string $version, ?OutputInterface $output): void
    {
        $command = $version === 'latest'
            ? 'npm install -g yarn'
            : "npm install -g yarn@{$version}";

        $this->runCommand($command, $output);
    }

    protected function installNpm(string $version, ?OutputInterface $output): void
    {
        $command = $version === 'latest'
            ? 'npm install -g npm@latest'
            : "npm install -g npm@{$version}";

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

        if (! $process->isSuccessful()) {
            throw new \RuntimeException("Failed to run command: {$command}");
        }
    }
}
