<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class StartDockerCommand extends Command
{
    protected $signature = 'os:start-docker';

    protected $description = 'Start Docker Desktop or Docker service';

    public function handle(): int
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => 'open -a Docker',
            'Windows' => 'start "" "C:\\Program Files\\Docker\\Docker\\Docker Desktop.exe"',
            default => 'systemctl --user start docker || sudo systemctl start docker',
        };

        $this->info('Starting Docker...');
        exec($command, $output, $resultCode);

        if ($resultCode === 0) {
            $this->info('Docker started successfully.');
        } else {
            $this->warn('Failed to start Docker. Please start it manually.');
        }

        return self::SUCCESS;
    }
}
