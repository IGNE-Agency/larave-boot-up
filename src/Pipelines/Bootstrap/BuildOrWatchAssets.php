<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Igne\LaravelBootstrap\Services\PackageJsonManager;
use Igne\LaravelBootstrap\Traits\OpensTerminalCommands;

final readonly class BuildOrWatchAssets
{
    use OpensTerminalCommands;

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

    private function buildOrWatchInSeparateTerminal(Serve $runner): void
    {
        $packageManager = $this->getPackageManager();
        $command = $this->getAssetCommand($packageManager);

        $this->executeInSeparateTerminal("{$packageManager->value} {$command}");
    }

    private function buildAssetsSynchronously(Serve $runner): void
    {
        $packageManager = $this->getPackageManager();
        $command = $packageManager->buildCommand();

        $runner->getOutput()?->info('Building frontend assets...');

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
