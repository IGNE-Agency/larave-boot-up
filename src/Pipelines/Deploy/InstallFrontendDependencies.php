<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Exception;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Igne\LaravelBootstrap\Managers\PackageJsonManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

final readonly class InstallFrontendDependencies
{
    public function __construct(
        private PackageJsonManager $packageJsonManager
    ) {
    }

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if ($this->shouldSkipFrontendSetup()) {
            $command->warn('package.json not found, skipping frontend setup.');

            return $next($command);
        }

        $packageManager = $this->getPackageManager($command);

        $this->prepareManager($command, $packageManager);
        $this->manageDependencies($command, $packageManager);
        $this->validateBuildWillSucceed($command, $packageManager);

        return $next($command);
    }

    private function shouldSkipFrontendSetup(): bool
    {
        return !$this->packageJsonManager->exists();
    }

    private function getPackageManager(InterruptibleCommand $command): PackageManager
    {
        return $command->externalProcessManager->getPackageManager();
    }

    private function prepareManager(InterruptibleCommand $command, PackageManager $packageManager): void
    {
        $this->cleanupOtherPackageManagerLockFiles($command, $packageManager);
        $this->updatePackageJsonEngines($command, $packageManager);
    }

    private function manageDependencies(InterruptibleCommand $command, PackageManager $packageManager): void
    {
        if ($this->shouldUpdateDependencies($command)) {
            $this->updateDependencies($command, $packageManager);

            return;
        }

        $this->installDependencies($command, $packageManager);
    }

    private function validateBuildWillSucceed(InterruptibleCommand $command, PackageManager $packageManager): void
    {
        $command->info('Validating build configuration...');

        $packageJson = $this->packageJsonManager->read();

        if ($packageJson === null) {
            return;
        }

        if (!isset($packageJson['scripts']['build']) && !isset($packageJson['scripts']['dev'])) {
            $command->warn('No build or dev script found in package.json. Asset building may fail.');
        }
    }

    private function shouldUpdateDependencies(InterruptibleCommand $command): bool
    {
        return $command->hasOption('update') && $command->option('update');
    }

    private function installDependencies(InterruptibleCommand $command, PackageManager $packageManager): void
    {
        $command->info('Installing dependencies...');

        try {
            $this->runInstallCommand($command, $packageManager);
        } catch (Exception $exception) {
            $this->handleInstallException($command, $packageManager, $exception);
        }
    }

    private function runInstallCommand(InterruptibleCommand $command, PackageManager $packageManager): void
    {
        $command->externalProcessManager->packageManager($packageManager->installCommand());
    }

    private function handleInstallException(
        InterruptibleCommand $command,
        PackageManager $packageManager,
        Exception $exception
    ): void {
        if ($this->isLockFileSyncIssue($exception->getMessage())) {
            $this->regenerateLockFile($command, $packageManager);
            $this->runInstallCommand($command, $packageManager);

            return;
        }

        throw $exception;
    }

    private function updateDependencies(InterruptibleCommand $command, PackageManager $packageManager): void
    {
        $command->info('Updating dependencies...');
        $command->externalProcessManager->packageManager($packageManager->updateCommand());
    }

    private function regenerateLockFile(InterruptibleCommand $command, PackageManager $packageManager): void
    {
        $command->warn('Lock file is out of sync with package.json.');
        $command->info('Regenerating lock file...');

        $this->deleteLockFile($packageManager);

        $command->info('Lock file removed. Installing dependencies...');
    }

    private function deleteLockFile(PackageManager $packageManager): void
    {
        $lockFilePath = $this->getLockFilePath($packageManager);

        if (File::exists($lockFilePath)) {
            File::delete($lockFilePath);
        }
    }

    private function getLockFilePath(PackageManager $packageManager): string
    {
        return base_path($packageManager->lockFile());
    }

    private function cleanupOtherPackageManagerLockFiles(
        InterruptibleCommand $command,
        PackageManager $packageManager
    ): void {
        $deletedFiles = $this->deleteOtherLockFiles($packageManager);

        if ($deletedFiles->isNotEmpty()) {
            $this->warnAboutDeletedLockFiles($command, $deletedFiles);
        }
    }

    /**
     * Will return deleted lock files
     * @param PackageManager $packageManager
     * @return Collection<int, string>
     */
    private function deleteOtherLockFiles(PackageManager $packageManager): Collection
    {
        return collect($packageManager->getOtherPackageManagers())
            ->filter(fn(PackageManager $manager): bool => File::exists($this->getLockFilePath($manager)))
            ->each($this->deleteLockFile(...))
            ->map(fn(PackageManager $manager): string => $manager->lockFile())
            ->values();
    }

    private function warnAboutDeletedLockFiles(InterruptibleCommand $command, Collection $deletedFiles): void
    {
        $fileList = $deletedFiles->implode(', ');
        $command->warn("Removed lock files from other package managers: {$fileList}");
    }

    private function updatePackageJsonEngines(InterruptibleCommand $command, PackageManager $packageManager): void
    {
        $packageJson = $this->packageJsonManager->read();

        if ($packageJson === null) {
            return;
        }

        $updatedPackageJson = $this->packageJsonManager->updateEngines($packageManager, $packageJson);

        if ($this->hasChanges($packageJson, $updatedPackageJson)) {
            $this->packageJsonManager->write($updatedPackageJson);
            $command->info('Updated package.json with engine requirements.');
        }
    }

    private function hasChanges(array $original, array $updated): bool
    {
        return $original !== $updated;
    }

    private function isLockFileSyncIssue(string $errorMessage): bool
    {
        return $this->getLockFileSyncPatterns()
            ->contains(fn(string $pattern): bool => $this->messageContainsPattern($errorMessage, $pattern));
    }

    private function getLockFileSyncPatterns(): Collection
    {
        return collect([
            'lock file',
            'lockfile',
            'out of sync',
            'out-of-date',
            'integrity check failed',
            'checksum',
            'corrupted',
        ]);
    }

    private function messageContainsPattern(string $message, string $pattern): bool
    {
        return stripos($message, $pattern) !== false;
    }
}
