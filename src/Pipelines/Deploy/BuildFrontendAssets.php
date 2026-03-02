<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Illuminate\Support\Facades\File;

final readonly class BuildFrontendAssets
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if (!File::exists(base_path('package.json'))) {
            $command->warn('package.json not found, skipping frontend setup.');

            return $next($command);
        }

        $pmEnum = $command->externalProcessManager->getPackageManager();

        if ($command->hasOption('update') && $command->option('update')) {
            $command->info('Updating dependencies...');
            $command->externalProcessManager->packageManager($pmEnum->updateCommand());
        }

        $command->info('Installing dependencies');
        $command->externalProcessManager->packageManager($pmEnum->installCommand());

        $command->info('Building frontend assets');
        $command->externalProcessManager->packageManager($pmEnum->buildCommand());

        return $next($command);
    }
}
