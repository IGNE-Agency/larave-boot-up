<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class KillProcessCommand extends Command
{
    protected $signature = 'os:kill-process {pid : The process ID to kill}';

    protected $description = 'Kill a process by PID';

    public function handle(): int
    {
        $pid = (int) $this->argument('pid');

        if ($pid <= 0) {
            $this->error('Invalid PID provided.');

            return self::FAILURE;
        }

        $command = match (PHP_OS_FAMILY) {
            'Windows' => "taskkill /PID {$pid} /F 2>NUL",
            default => "kill -TERM -{$pid} 2>/dev/null || kill -TERM {$pid} 2>/dev/null",
        };

        $this->info("Killing process {$pid}...");
        exec($command, $output, $resultCode);

        if ($resultCode === 0) {
            $this->info("Process {$pid} terminated successfully.");
        } else {
            $this->warn("Failed to terminate process {$pid}.");
        }

        return self::SUCCESS;
    }
}
