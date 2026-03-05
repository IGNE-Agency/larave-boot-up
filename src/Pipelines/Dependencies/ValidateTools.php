<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Dependencies;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Development\ToolInstaller;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Igne\LaravelBootstrap\Verifiers\VersionChecker;
use Illuminate\Support\Collection;

final readonly class ValidateTools
{
    public function __construct(
        private ToolInstaller $installer = new ToolInstaller,
        private VersionChecker $versionChecker = new VersionChecker
    ) {}

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        // TODO:  fix
        $serverArgument = $command->argument('server');
        if ($serverArgument && ! $serverArgument instanceof \Igne\LaravelBootstrap\Enums\DevServerOption) {
            $serverOption = \Igne\LaravelBootstrap\Enums\DevServerOption::from($serverArgument);
        }

        $this->installer->setServer($serverOption);
        $this->validateAllTools($command);

        return $next($command);
    }

    private function validateAllTools(InterruptibleCommand $command): void
    {
        $this->getToolsToValidate()
            ->each(fn (string $tool) => $this->validateTool($tool, $command));
    }

    private function getToolsToValidate(): Collection
    {
        $configuredTools = $this->getConfiguredTools();
        $packageManager = $this->getDefaultPackageManager();

        return collect($configuredTools)->push($packageManager);
    }

    private function getConfiguredTools(): array
    {
        return config('bootstrap.auto_install.tools', ['php', 'node', 'composer']);
    }

    private function getDefaultPackageManager(): string
    {
        return config('bootstrap.package_manager.default', 'bun');
    }

    private function isAutoInstallEnabled(): bool
    {
        return config('bootstrap.auto_install.enabled', true);
    }

    private function validateTool(string $tool, InterruptibleCommand $command): void
    {
        if ($this->isToolMissing($tool, $command)) {
            $this->handleMissingTool($tool, $command);

            return;
        }

        $this->validateToolVersion($tool, $command);
    }

    private function isToolMissing(string $tool, InterruptibleCommand $command): bool
    {
        return ! $command->externalProcessManager->isCommandAvailable($tool);
    }

    private function validateToolVersion(string $tool, InterruptibleCommand $command): void
    {
        $currentVersion = $this->getToolVersion($tool, $command);
        $requiredVersion = $this->getRequiredVersion($tool);

        if ($this->isLatestVersionRequired($requiredVersion)) {
            $this->handleLatestVersion($tool, $currentVersion, $command);

            return;
        }

        $this->handleSpecificVersion($tool, $currentVersion, $requiredVersion, $command);
    }

    private function getRequiredVersion(string $tool): string
    {
        return config("bootstrap.tools.{$tool}", 'latest');
    }

    private function isLatestVersionRequired(string $version): bool
    {
        return $version === 'latest';
    }

    private function handleMissingTool(string $tool, InterruptibleCommand $command): void
    {
        if (! $this->isAutoInstallEnabled()) {
            $this->throwMissingToolException($tool);
        }

        $this->installMissingTool($tool, $command);
    }

    private function throwMissingToolException(string $tool): void
    {
        throw new DependencyCheckException("{$tool} not found. Please install it manually.");
    }

    private function installMissingTool(string $tool, InterruptibleCommand $command): void
    {
        $version = $this->getRequiredVersion($tool);

        $this->displayInstallingMessage($tool, $command);
        $this->installer->install($tool, $version, $command->getOutput());
    }

    private function displayInstallingMessage(string $tool, InterruptibleCommand $command): void
    {
        $command->warn("{$tool} not found. Installing...");
    }

    private function handleLatestVersion(string $tool, string $currentVersion, InterruptibleCommand $command): void
    {
        $latestVersion = $this->getLatestVersion($tool);

        if ($this->isVersionSufficient($currentVersion, $latestVersion)) {
            $this->displayVersionOk($tool, $currentVersion, true, $command);

            return;
        }

        $this->handleOutdatedVersion($tool, $currentVersion, $latestVersion, $command);
    }

    private function getLatestVersion(string $tool): string
    {
        return $this->versionChecker->getLatestSafeVersion($tool);
    }

    private function isVersionSufficient(string $currentVersion, string $requiredVersion): bool
    {
        return version_compare($currentVersion, $requiredVersion, '>=');
    }

    private function displayVersionOk(string $tool, string $version, bool $isLatest, InterruptibleCommand $command): void
    {
        $suffix = $isLatest ? ' (latest)' : '';
        $command->line("{$tool} {$version} OK{$suffix}.");
    }

    private function handleOutdatedVersion(
        string $tool,
        string $currentVersion,
        string $latestVersion,
        InterruptibleCommand $command
    ): void {
        if ($this->isAutoInstallEnabled()) {
            $this->updateTool($tool, $currentVersion, $latestVersion, $command);

            return;
        }

        $this->displayOutdatedWarning($tool, $currentVersion, $latestVersion, $command);
    }

    private function updateTool(
        string $tool,
        string $currentVersion,
        string $targetVersion,
        InterruptibleCommand $command
    ): void {
        $this->displayUpdatingMessage($tool, $currentVersion, $targetVersion, $command);
        $this->installer->update($tool, $targetVersion, $command->getOutput());
    }

    private function displayUpdatingMessage(
        string $tool,
        string $currentVersion,
        string $targetVersion,
        InterruptibleCommand $command
    ): void {
        $command->warn("{$tool} {$currentVersion} is outdated. Updating to {$targetVersion}...");
    }

    private function displayOutdatedWarning(
        string $tool,
        string $currentVersion,
        string $latestVersion,
        InterruptibleCommand $command
    ): void {
        $command->warn("{$tool} {$currentVersion} is outdated. Latest: {$latestVersion}");
    }

    private function handleSpecificVersion(
        string $tool,
        string $currentVersion,
        string $requiredVersion,
        InterruptibleCommand $command
    ): void {
        if ($this->isVersionSufficient($currentVersion, $requiredVersion)) {
            $this->displayVersionOk($tool, $currentVersion, false, $command);

            return;
        }

        $this->handleInsufficientVersion($tool, $currentVersion, $requiredVersion, $command);
    }

    private function handleInsufficientVersion(
        string $tool,
        string $currentVersion,
        string $requiredVersion,
        InterruptibleCommand $command
    ): void {
        if ($this->isAutoInstallEnabled()) {
            $this->updateTool($tool, $currentVersion, $requiredVersion, $command);

            return;
        }

        $this->throwInsufficientVersionException($tool, $currentVersion, $requiredVersion);
    }

    private function throwInsufficientVersionException(
        string $tool,
        string $currentVersion,
        string $requiredVersion
    ): void {
        throw new DependencyCheckException("{$tool} {$currentVersion} too old. Required: >= {$requiredVersion}");
    }

    private function getToolVersion(string $tool, InterruptibleCommand $command): string
    {
        $rawOutput = $this->getToolVersionOutput($tool, $command);
        $version = $this->extractVersionFromOutput($rawOutput);

        return $version ?: $this->getDefaultVersion();
    }

    private function getToolVersionOutput(string $tool, InterruptibleCommand $command): string
    {
        $process = $command->externalProcessManager->callSilent("{$tool} -v");

        return $process->getOutput();
    }

    private function extractVersionFromOutput(string $output): string
    {
        $trimmedOutput = \Illuminate\Support\Str::of($output)->trim();
        $version = $trimmedOutput->match('/v?(\d+\.\d+\.\d+)/');

        return $version->toString();
    }

    private function getDefaultVersion(): string
    {
        return '0.0.0';
    }
}
