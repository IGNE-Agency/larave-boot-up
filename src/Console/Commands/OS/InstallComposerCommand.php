<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallComposerCommand extends Command
{
    protected $signature = 'os:install-composer';

    protected $description = 'Install Composer globally';

    public function handle(): int
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => $this->buildWindowsCommand(),
            default => $this->buildUnixCommand(),
        };

        $this->info('Installing Composer...');
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function buildWindowsCommand(): string
    {
        return 'powershell -c "Invoke-WebRequest -Uri https://getcomposer.org/installer -OutFile composer-setup.php; '.
            'php composer-setup.php --install-dir=%USERPROFILE%\\AppData\\Roaming\\Composer --filename=composer.bat; '.
            'Remove-Item composer-setup.php"';
    }

    private function buildUnixCommand(): string
    {
        return 'curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer';
    }
}
