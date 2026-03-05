<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallNpmCommand extends Command
{
    protected $signature = 'os:install-npm {version=latest : The npm version to install}';

    protected $description = 'Install or update npm package manager';

    public function handle(): int
    {
        $version = $this->argument('version');
        $versionSuffix = $version === 'latest' ? '@latest' : "@{$version}";

        $command = "npm install -g npm{$versionSuffix}";

        $this->info("Installing npm {$version}...");
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
