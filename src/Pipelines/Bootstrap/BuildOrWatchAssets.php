<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Server;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Igne\LaravelBootstrap\Managers\PackageJsonManager;
use Igne\LaravelBootstrap\Resolvers\ConfigResolver;
use Igne\LaravelBootstrap\Traits\OpensTerminalCommands;

final readonly class BuildOrWatchAssets
{
    use OpensTerminalCommands;

    public function __construct(
        private PackageJsonManager $packageJsonManager,
        private ConfigResolver $configResolver
    ) {}

    public function handle(Server $server, Closure $next): Server
    {
        if ($this->shouldSkipAssetBuilding()) {
            return $next($server);
        }

        if ($this->configResolver->shouldUseSeparateTerminal() && $this->canOpenTerminal()) {
            $this->buildOrWatchInSeparateTerminal();
        } else {
            $this->buildAssetsSynchronously($server);
        }

        return $next($server);
    }

    private function shouldSkipAssetBuilding(): bool
    {
        return ! $this->packageJsonManager->exists();
    }

    private function buildOrWatchInSeparateTerminal(): void
    {
        $packageManager = $this->getPackageManager();
        $command = $this->getAssetCommand($packageManager);

        $this->executeInSeparateTerminal("{$packageManager->value} {$command}");
    }

    private function buildAssetsSynchronously(Server $server): void
    {
        $packageManager = $this->getPackageManager();
        $command = $packageManager->buildCommand();

        $server->getOutput()?->info('Building frontend assets...');

        shell_exec("{$packageManager->value} {$command}");
    }

    private function getPackageManager(): PackageManager
    {
        $default = config('bootstrap.package_manager.default', 'bun');

        return PackageManager::from($default);
    }

    private function getAssetCommand(PackageManager $packageManager): string
    {
        if ($this->configResolver->shouldWatchAssets()) {
            return $packageManager->devCommand();
        }

        return $packageManager->buildCommand();
    }
}
