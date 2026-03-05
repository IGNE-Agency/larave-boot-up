<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Managers;

use Igne\LaravelBootstrap\Enums\PackageManager;
use Illuminate\Support\Facades\File;

final class PackageJsonManager
{
    public function exists(): bool
    {
        return File::exists($this->getPath());
    }

    public function read(): ?array
    {
        $content = $this->decode();

        return \is_array($content) ? $content : null;
    }

    public function write(array $packageJson): void
    {
        $encodedContent = $this->encode($packageJson);
        File::put($this->getPath(), $encodedContent);
    }

    public function updateEngines(PackageManager $packageManager, array $packageJson): array
    {
        $packageJson['engines'] = $packageJson['engines'] ?? [];
        $packageJson['engines'] = $this->buildEnginesConfig($packageManager, $packageJson['engines']);
        $packageJson = $this->setPackageManagerField($packageManager, $packageJson);

        return $packageJson;
    }

    private function getPath(): string
    {
        return base_path('package.json');
    }

    private function decode(): mixed
    {
        $fileContent = File::get($this->getPath());

        return json_decode($fileContent, true);
    }

    private function encode(array $packageJson): string
    {
        return json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    private function buildEnginesConfig(PackageManager $packageManager, array $engines): array
    {
        $managerEngines = $this->buildPackageManagerEngines($packageManager);
        $nodeEngine = $this->buildNodeEngine();

        return [...$engines, ...$managerEngines, ...$nodeEngine];
    }

    private function buildPackageManagerEngines(PackageManager $packageManager): array
    {
        $version = $this->getPackageManagerVersion($packageManager);

        return collect(PackageManager::cases())
            ->mapWithKeys(fn (PackageManager $manager): array => [
                $manager->value => $this->getEngineConstraint($manager, $packageManager, $version),
            ])
            ->all();
    }

    private function getEngineConstraint(
        PackageManager $manager,
        PackageManager $currentPackageManager,
        ?string $version
    ): string {
        if ($manager === $currentPackageManager && $version !== null) {
            return ">= {$version}";
        }

        return "please-use-{$currentPackageManager->value}";
    }

    private function buildNodeEngine(): array
    {
        $nodeVersion = $this->getNodeVersion();

        if ($this->isLatestVersion($nodeVersion)) {
            return [];
        }

        return ['node' => ">= {$nodeVersion}"];
    }

    private function getNodeVersion(): string
    {
        return config('bootstrap.tools.node', 'latest');
    }

    private function isLatestVersion(?string $version): bool
    {
        return $version === 'latest' || $version === null;
    }

    private function setPackageManagerField(PackageManager $packageManager, array $packageJson): array
    {
        $version = $this->getPackageManagerVersion($packageManager);

        if ($version !== null) {
            $packageJson['packageManager'] = "{$packageManager->value}@{$version}";
        }

        return $packageJson;
    }

    private function getPackageManagerVersion(PackageManager $packageManager): ?string
    {
        $version = config("bootstrap.tools.{$packageManager->value}");

        if ($this->isLatestVersion($version)) {
            return null;
        }

        return $version;
    }
}
