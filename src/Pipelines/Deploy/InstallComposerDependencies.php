<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Illuminate\Support\Facades\File;

final readonly class InstallComposerDependencies
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $this->validateComposerJson($command);

        $shouldUpdate = $command->hasOption('update') && $command->option('update');

        if ($shouldUpdate) {
            $this->updateDependencies($command);
        } else {
            $this->installDependencies($command);
        }

        return $next($command);
    }

    private function validateComposerJson(InterruptibleCommand $command): void
    {
        if (!File::exists(base_path('composer.json'))) {
            $command->error('composer.json not found in project root.');
            $command->newLine();
            $command->line('This package requires a valid Laravel project with composer.json.');
            $command->line('Please ensure you are running this command from a Laravel project root.');

            throw new DependencyCheckException('composer.json file is required but not found in project root.');
        }
    }

    private function installDependencies(InterruptibleCommand $command): void
    {
        $command->info('Installing dependencies...');

        try {
            $command->externalProcessManager->composer('install', ['--no-interaction', '--prefer-dist']);
        } catch (\Exception $e) {
            if ($this->isLockFileSyncIssue($e->getMessage())) {
                $this->regenerateLockFile($command);
                $command->externalProcessManager->composer('install', ['--no-interaction', '--prefer-dist']);
            } else {
                throw $e;
            }
        }
    }

    private function updateDependencies(InterruptibleCommand $command): void
    {
        $command->info('Updating dependencies...');
        $command->externalProcessManager->composer('update', ['--no-interaction', '--prefer-dist']);
        $command->externalProcessManager->composer('run upgrade');
    }

    private function regenerateLockFile(InterruptibleCommand $command): void
    {
        $command->warn('Lock file is out of sync with composer.json.');
        $command->info('Regenerating lock file without updating package versions...');

        $command->externalProcessManager->composer('update', ['--lock', '--no-interaction']);

        $command->info('Lock file regenerated. Installing dependencies...');
    }

    private function isLockFileSyncIssue(string $errorMessage): bool
    {
        $patterns = [
            'lock file is not up to date',
            'hash does not match',
            'content-hash',
            'lock file out of date',
            'run `composer update`',
        ];

        foreach ($patterns as $pattern) {
            if (stripos($errorMessage, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
