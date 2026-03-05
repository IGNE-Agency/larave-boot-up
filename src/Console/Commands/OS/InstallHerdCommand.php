<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class InstallHerdCommand extends Command
{
    protected $signature = 'os:install-herd';

    protected $description = 'Install Laravel Herd (macOS only)';

    public function handle(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->error('Laravel Herd for Windows requires manual installation with administrator privileges.');
            $this->line('Please download the installer from: https://herd.laravel.com/windows');
            $this->line('After installation, you may need to add %USERPROFILE%\.config\herd to Windows Defender exclusions for better performance.');

            return self::FAILURE;
        }

        if (PHP_OS_FAMILY !== 'Darwin') {
            $this->error('Laravel Herd is only available for macOS 12.0+ and Windows 10+.');
            $this->line('Please download it manually from: https://herd.laravel.com');
            $this->line('For Linux, consider using Laravel Sail (Docker) or the built-in Laravel development server.');

            return self::FAILURE;
        }

        $brewInstall = '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"';
        $command = "command -v brew > /dev/null 2>&1 || {$brewInstall}; brew install herd";

        $this->info('Installing Laravel Herd...');
        passthru($command, $resultCode);

        return $resultCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
