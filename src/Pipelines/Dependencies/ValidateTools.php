<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Dependencies;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Igne\LaravelBootstrap\Services\ToolInstaller;
use Igne\LaravelBootstrap\Services\VersionChecker;

final readonly class ValidateTools
{
    public function __construct(
        private ToolInstaller $installer = new ToolInstaller(),
        private VersionChecker $versionChecker = new VersionChecker()
    ) {
    }

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $autoInstall = config('bootstrap.auto_install.enabled', true);
        $tools = config('bootstrap.auto_install.tools', ['php', 'node', 'composer']);
        $packageManager = config('bootstrap.package_manager.default', 'bun');

        collect($tools)
            ->push($packageManager)
            ->each(fn($tool) => $this->validateTool($tool, $autoInstall, $command));

        return $next($command);
    }

    private function validateTool(string $tool, bool $autoInstall, InterruptibleCommand $command): void
    {
        $requiredVersion = config("bootstrap.tools.{$tool}", 'latest');

        if (!$command->externalProcessManager->isCommandAvailable($tool)) {
            $this->handleMissingTool($tool, $requiredVersion, $autoInstall, $command);
            return;
        }

        $currentVersion = $this->getToolVersion($tool, $command);

        if ($requiredVersion === 'latest') {
            $this->handleLatestVersion($tool, $currentVersion, $autoInstall, $command);
        } else {
            $this->handleSpecificVersion($tool, $currentVersion, $requiredVersion, $autoInstall, $command);
        }
    }

    private function handleMissingTool(string $tool, string $version, bool $autoInstall, InterruptibleCommand $command): void
    {
        if (!$autoInstall) {
            throw new DependencyCheckException("{$tool} not found. Please install it manually.");
        }

        $command->warn("{$tool} not found. Installing...");
        $this->installer->install($tool, $version, $command->getOutput());
    }

    private function handleLatestVersion(string $tool, string $currentVersion, bool $autoInstall, InterruptibleCommand $command): void
    {
        $latestVersion = $this->versionChecker->getLatestSafeVersion($tool);

        if (version_compare($currentVersion, $latestVersion, '>=')) {
            $command->line("{$tool} {$currentVersion} OK (latest).");
            return;
        }

        if ($autoInstall) {
            $command->warn("{$tool} {$currentVersion} is outdated. Updating to {$latestVersion}...");
            $this->installer->update($tool, $latestVersion, $command->getOutput());
        } else {
            $command->warn("{$tool} {$currentVersion} is outdated. Latest: {$latestVersion}");
        }
    }

    private function handleSpecificVersion(string $tool, string $currentVersion, string $requiredVersion, bool $autoInstall, InterruptibleCommand $command): void
    {
        if (version_compare($currentVersion, $requiredVersion, '>=')) {
            $command->line("{$tool} {$currentVersion} OK.");
            return;
        }

        if ($autoInstall) {
            $command->warn("{$tool} {$currentVersion} too old. Installing {$requiredVersion}...");
            $this->installer->update($tool, $requiredVersion, $command->getOutput());
        } else {
            throw new DependencyCheckException("{$tool} {$currentVersion} too old. Required: >= {$requiredVersion}");
        }
    }

    private function getToolVersion(string $toolName, InterruptibleCommand $command): string
    {
        $process = $command->externalProcessManager->callSilent("{$toolName} -v");

        return \Illuminate\Support\Str::of($process->getOutput())
            ->trim()
            ->ltrim('v')
            ->match('/\d+(\.\d+)+/')
            ->toString() ?: '0.0.0';
    }
}
