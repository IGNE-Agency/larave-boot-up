<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallBunCommand extends Command
{
    protected $signature = 'os:install-bun {version=latest : The Bun version to install}';

    protected $description = 'Install Bun JavaScript runtime';

    public function handle(): int
    {
        $version = $this->argument('version');

        $command = match (PHP_OS_FAMILY) {
            'Windows' => $this->buildWindowsCommand($version),
            default => $this->buildUnixCommand($version),
        };

        $this->info("Installing Bun {$version}...");
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function buildWindowsCommand(string $version): string
    {
        $cmd = 'powershell -c "irm bun.sh/install.ps1|iex';

        return $version === 'latest' ? $cmd.'"' : $cmd."; bun upgrade --version {$version}\"";
    }

    private function buildUnixCommand(string $version): string
    {
        $cmd = 'curl -fsSL https://bun.sh/install | bash';

        return $version === 'latest' ? $cmd : $cmd." -s \"bun-v{$version}\"";
    }
}
