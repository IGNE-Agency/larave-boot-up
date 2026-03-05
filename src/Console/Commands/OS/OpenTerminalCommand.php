<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class OpenTerminalCommand extends Command
{
    protected $signature = 'os:open-terminal {command? : The command to run in the terminal}';

    protected $description = 'Open a new terminal window with an optional command';

    public function handle(): int
    {
        $command = $this->argument('command') ?? 'php artisan';
        $basePath = base_path();

        $shellCommand = match (PHP_OS_FAMILY) {
            'Darwin' => $this->buildMacOSCommand($basePath, $command),
            'Windows' => "start cmd /k \"cd /d {$basePath} && {$command}\"",
            'Linux' => $this->buildLinuxCommand($basePath, $command),
            default => null,
        };

        if ($shellCommand === null) {
            $this->error('Unsupported operating system.');

            return self::FAILURE;
        }

        $this->info('Opening terminal...');
        exec($shellCommand);

        return self::SUCCESS;
    }

    private function buildMacOSCommand(string $basePath, string $command): string
    {
        $escapedCommand = str_replace('"', '\\"', $command);

        return sprintf(
            "osascript -e 'tell app \"Terminal\" to do script \"cd %s && %s\"'",
            $basePath,
            $escapedCommand
        );
    }

    private function buildLinuxCommand(string $basePath, string $command): string
    {
        $terminal = File::exists('/usr/bin/gnome-terminal') ? 'gnome-terminal' : 'xterm';
        $flag = $terminal === 'gnome-terminal' ? '--' : '-e';

        return "{$terminal} {$flag} bash -c 'cd {$basePath} && {$command}; exec bash'";
    }
}
