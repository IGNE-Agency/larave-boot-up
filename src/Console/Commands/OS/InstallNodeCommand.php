<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallNodeCommand extends Command
{
    protected $signature = 'os:install-node {version=latest : The Node.js version to install}';

    protected $description = 'Install Node.js using the system package manager';

    public function handle(): int
    {
        $version = $this->argument('version');

        $command = match (PHP_OS_FAMILY) {
            'Darwin' => $this->buildMacOSCommand($version),
            'Windows' => $this->buildWindowsCommand($version),
            default => $this->buildLinuxCommand($version),
        };

        $this->info("Installing Node.js {$version}...");
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function buildMacOSCommand(string $version): string
    {
        $versionSuffix = $version === 'latest' ? '' : "@{$version}";
        $brewInstall = '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"';

        return "command -v brew > /dev/null 2>&1 || {$brewInstall}; brew install node{$versionSuffix}";
    }

    private function buildWindowsCommand(string $version): string
    {
        $versionFlag = $version === 'latest' ? '' : " --version {$version}";

        return "winget install OpenJS.NodeJS{$versionFlag}";
    }

    private function buildLinuxCommand(string $version): string
    {
        $setupVersion = $version === 'latest' ? 'lts' : $version;

        return "curl -fsSL https://deb.nodesource.com/setup_{$setupVersion}.x | sudo -E bash - && sudo apt-get install -y nodejs";
    }
}
