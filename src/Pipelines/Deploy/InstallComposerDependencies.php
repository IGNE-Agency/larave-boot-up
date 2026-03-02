<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Illuminate\Support\Facades\File;

final readonly class InstallComposerDependencies
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if (!File::exists(base_path('composer.json'))) {
            $command->warn('composer.json not found, skipping installing dependencies.');

            return $next($command);
        }

        if ($command->hasOption('update') && $command->option('update')) {
            $command->info('Updating dependencies...');
            $command->externalProcessManager->composer('run upgrade');
        }

        $command->info('Installing dependencies...');
        $command->externalProcessManager->composer('install', ['--no-interaction', '--prefer-dist']);

        return $next($command);
    }
}
