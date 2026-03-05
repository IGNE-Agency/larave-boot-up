<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallDockerCommand extends Command
{
    protected $signature = 'os:install-docker';

    protected $description = 'Install Docker Desktop or Docker Engine';

    public function handle(): int
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => $this->buildMacOSCommand(),
            'Windows' => 'winget install Docker.DockerDesktop',
            default => 'curl -fsSL https://get.docker.com | sh && sudo usermod -aG docker $USER',
        };

        $this->info('Installing Docker...');
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function buildMacOSCommand(): string
    {
        $brewInstall = '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"';

        return "command -v brew > /dev/null 2>&1 || {$brewInstall}; brew install docker";
    }
}
