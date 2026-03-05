<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class KillPhpArtisanCommand extends Command
{
    protected $signature = 'os:kill-php-artisan';

    protected $description = 'Kill running PHP Artisan serve processes';

    public function handle(): int
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => 'taskkill /F /IM php.exe /FI "WINDOWTITLE eq *artisan serve*"',
            default => 'pkill -f "php artisan serve"',
        };

        $this->info('Killing PHP Artisan serve processes...');
        exec($command, $output, $resultCode);

        if ($resultCode === 0) {
            $this->info('PHP Artisan processes terminated successfully.');
        }

        return self::SUCCESS;
    }
}
