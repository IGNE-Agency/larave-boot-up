<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Exception;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

final readonly class InstallComposerDependencies
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $this->validateComposerJson($command);
        $this->manageDependencies($command);

        return $next($command);
    }

    private function manageDependencies(InterruptibleCommand $command): void
    {
        if ($this->shouldUpdateDependencies($command)) {
            $this->updateDependencies($command);

            return;
        }

        $this->installDependencies($command);
    }

    private function shouldUpdateDependencies(InterruptibleCommand $command): bool
    {
        return $command->hasOption('update') && $command->option('update');
    }

    private function validateComposerJson(InterruptibleCommand $command): void
    {
        if ($this->composerJsonExists()) {
            return;
        }

        $this->displayComposerJsonError($command);
        $this->throwComposerJsonException();
    }

    private function composerJsonExists(): bool
    {
        return File::exists($this->getComposerJsonPath());
    }

    private function getComposerJsonPath(): string
    {
        return base_path('composer.json');
    }

    private function displayComposerJsonError(InterruptibleCommand $command): void
    {
        $command->error('composer.json not found in project root.');
        $command->newLine();
        $command->line('This package requires a valid Laravel project with composer.json.');
        $command->line('Please ensure you are running this command from a Laravel project root.');
    }

    private function throwComposerJsonException(): void
    {
        throw new DependencyCheckException('composer.json file is required but not found in project root.');
    }

    private function installDependencies(InterruptibleCommand $command): void
    {
        $command->info('Installing dependencies...');

        try {
            $this->runInstallCommand($command);
        } catch (Exception $exception) {
            $this->handleInstallException($command, $exception);
        }
    }

    private function runInstallCommand(InterruptibleCommand $command): void
    {
        $command->externalProcessManager->composer('install', $this->getInstallFlags());
    }

    private function getInstallFlags(): array
    {
        return ['--no-interaction', '--prefer-dist'];
    }

    private function handleInstallException(InterruptibleCommand $command, Exception $exception): void
    {
        if ($this->isLockFileSyncIssue($exception->getMessage())) {
            $this->regenerateLockFile($command);
            $this->runInstallCommand($command);

            return;
        }

        throw $exception;
    }

    private function updateDependencies(InterruptibleCommand $command): void
    {
        $command->info('Updating dependencies...');
        $this->runUpdateCommand($command);
        $this->runUpgradeCommand($command);
    }

    private function runUpdateCommand(InterruptibleCommand $command): void
    {
        $command->externalProcessManager->composer('update', $this->getInstallFlags());
    }

    private function runUpgradeCommand(InterruptibleCommand $command): void
    {
        $command->externalProcessManager->composer('run upgrade');
    }

    private function regenerateLockFile(InterruptibleCommand $command): void
    {
        $this->displayRegenerationWarnings($command);
        $this->runLockFileRegeneration($command);
        $this->displayRegenerationSuccess($command);
    }

    private function displayRegenerationWarnings(InterruptibleCommand $command): void
    {
        $command->warn('Lock file is out of sync with composer.json.');
        $command->info('Regenerating lock file without updating package versions...');
    }

    private function runLockFileRegeneration(InterruptibleCommand $command): void
    {
        $command->externalProcessManager->composer('update', $this->getLockUpdateFlags());
    }

    private function getLockUpdateFlags(): array
    {
        return ['--lock', '--no-interaction'];
    }

    private function displayRegenerationSuccess(InterruptibleCommand $command): void
    {
        $command->info('Lock file regenerated. Installing dependencies...');
    }

    private function isLockFileSyncIssue(string $errorMessage): bool
    {
        return $this->getLockFileSyncPatterns()
            ->contains(fn(string $pattern): bool => $this->messageContainsPattern($errorMessage, $pattern));
    }

    private function getLockFileSyncPatterns(): Collection
    {
        return collect([
            'lock file is not up to date',
            'hash does not match',
            'content-hash',
            'lock file out of date',
            'run `composer update`',
        ]);
    }

    private function messageContainsPattern(string $message, string $pattern): bool
    {
        return stripos($message, $pattern) !== false;
    }
}
