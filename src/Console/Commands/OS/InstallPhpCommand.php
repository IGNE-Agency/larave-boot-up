<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallPhpCommand extends Command
{
    protected $signature = 'os:install-php {version=latest : The PHP version to install}';

    protected $description = 'Install PHP using the system package manager';

    public function handle(): int
    {
        $version = $this->argument('version');

        $command = match (PHP_OS_FAMILY) {
            'Darwin' => $this->buildMacOSCommand($version),
            'Windows' => $this->buildWindowsCommand($version),
            default => $this->buildLinuxCommand($version),
        };

        $this->info("Installing PHP {$version}...");
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function buildMacOSCommand(string $version): string
    {
        $versionSuffix = $version === 'latest' ? '' : "@{$version}";
        $brewInstall = '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"';

        return "command -v brew > /dev/null 2>&1 || {$brewInstall}; brew install php{$versionSuffix}";
    }

    private function buildWindowsCommand(string $version): string
    {
        $versionFlag = $version === 'latest' ? '' : " --version {$version}";

        return "winget install shivammathur.php{$versionFlag}";
    }

    private function buildLinuxCommand(string $version): string
    {
        $phpVersion = $version === 'latest' ? 'php' : "php{$version}";
        $extensions = ['cli', 'mbstring', 'xml', 'zip'];
        $packages = array_map(fn ($ext) => "{$phpVersion}-{$ext}", $extensions);

        return 'sudo apt-get update && sudo apt-get install -y '.$phpVersion.' '.implode(' ', $packages);
    }
}
