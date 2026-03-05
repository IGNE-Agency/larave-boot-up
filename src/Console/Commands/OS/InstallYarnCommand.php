<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallYarnCommand extends Command
{
    protected $signature = 'os:install-yarn {version=latest : The Yarn version to install}';

    protected $description = 'Install Yarn package manager via npm';

    public function handle(): int
    {
        $version = $this->argument('version');
        $versionSuffix = $version === 'latest' ? '' : "@{$version}";

        $command = "npm install -g yarn{$versionSuffix}";

        $this->info("Installing Yarn {$version}...");
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
