<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class CheckProcessCommand extends Command
{
    protected $signature = 'os:check-process {process : The process name to check}';

    protected $description = 'Check if a process is running';

    public function handle(): int
    {
        $process = $this->argument('process');

        $command = match (PHP_OS_FAMILY) {
            'Windows' => "tasklist /FI \"IMAGENAME eq {$process}.exe\" /NH",
            default => "pgrep -f {$process}",
        };

        exec($command, $output, $resultCode);

        if ($resultCode === 0 && ! empty($output)) {
            $this->info("Process '{$process}' is running.");
            $this->line(implode("\n", $output));

            return self::SUCCESS;
        }

        $this->warn("Process '{$process}' is not running.");

        return self::FAILURE;
    }
}
