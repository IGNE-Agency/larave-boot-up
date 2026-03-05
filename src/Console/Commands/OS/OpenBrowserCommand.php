<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Console\Commands\OS;

use Illuminate\Console\Command;

final class OpenBrowserCommand extends Command
{
    protected $signature = 'os:open-browser {url : The URL to open in the browser}';

    protected $description = 'Open a URL in the default browser';

    public function handle(): int
    {
        $url = $this->argument('url');

        $command = match (PHP_OS_FAMILY) {
            'Darwin' => "open {$url}",
            'Windows' => "start {$url}",
            'Linux' => "xdg-open {$url} || sensible-browser {$url} || x-www-browser {$url}",
            default => null,
        };

        if ($command === null) {
            $this->error('Unsupported operating system.');

            return self::FAILURE;
        }

        $this->info("Opening {$url} in browser...");
        exec($command);

        return self::SUCCESS;
    }
}
