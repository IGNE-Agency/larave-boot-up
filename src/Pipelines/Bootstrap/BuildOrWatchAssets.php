<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Igne\LaravelBootstrap\Services\PackageJsonManager;

final readonly class BuildOrWatchAssets
{
    public function __construct(
        private PackageJsonManager $packageJsonManager
    ) {
    }

    public function handle(Serve $runner, Closure $next): Serve
    {
        if ($this->shouldSkipAssetBuilding()) {
            return $next($runner);
        }

        $separateTerminal = config('bootstrap.assets.separate_terminal', true);

        if ($separateTerminal && $this->canOpenTerminal()) {
            $this->buildOrWatchInSeparateTerminal($runner);
        } else {
            $this->buildAssetsSynchronously($runner);
        }

        return $next($runner);
    }

    private function shouldSkipAssetBuilding(): bool
    {
        return !$this->packageJsonManager->exists();
    }

    private function canOpenTerminal(): bool
    {
        return OSCommand::OPEN_TERMINAL->canExecute();
    }

    private function buildOrWatchInSeparateTerminal(Serve $runner): void
    {
        $packageManager = $this->getPackageManager();
        $command = $this->getAssetCommand($packageManager);

        $terminalCommand = OSCommand::OPEN_TERMINAL
            ->withCommand("{$packageManager->value} {$command}")
            ->execute();

        if ($terminalCommand) {
            shell_exec("{$terminalCommand} > /dev/null 2>&1 &");
        }
    }

    private function buildAssetsSynchronously(Serve $runner): void
    {
        $packageManager = $this->getPackageManager();
        $command = $packageManager->buildCommand();

        $output = $runner->getOutput();
        if ($output) {
            $output->info('Building frontend assets...');
        }

        shell_exec("{$packageManager->value} {$command}");
    }

    private function getPackageManager(): PackageManager
    {
        $default = config('bootstrap.package_manager.default', 'bun');
        return PackageManager::from($default);
    }

    private function getAssetCommand(PackageManager $packageManager): string
    {
        $watchInDev = config('bootstrap.assets.watch_in_dev', true);
        $isDev = app()->environment('local', 'development');

        if ($watchInDev && $isDev) {
            return $packageManager->devCommand();
        }

        return $packageManager->buildCommand();
    }
}
