<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallHomebrewCommand extends Command
{
    protected $signature = 'os:install-homebrew';

    protected $description = 'Install Homebrew package manager (macOS/Linux)';

    public function handle(): int
    {
        if (! \in_array(PHP_OS_FAMILY, ['Darwin', 'Linux'])) {
            $this->error('Homebrew is only available for macOS and Linux.');

            return self::FAILURE;
        }

        $command = '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"';

        $this->info('Installing Homebrew...');
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
