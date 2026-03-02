<?php

declare(strict_types=1);

use Igne\LaravelBootstrap\Enums\PackageManager;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;

test('package manager enum has expected values', function () {
    expect(PackageManager::cases())->toHaveCount(3);
    expect(PackageManager::BUN)->toBeInstanceOf(PackageManager::class);
    expect(PackageManager::YARN)->toBeInstanceOf(PackageManager::class);
    expect(PackageManager::NPM)->toBeInstanceOf(PackageManager::class);
});

test('external command runner enum has expected values', function () {
    expect(ExternalCommandRunner::cases())->toHaveCount(3);
    expect(ExternalCommandRunner::HERD)->toBeInstanceOf(ExternalCommandRunner::class);
    expect(ExternalCommandRunner::SAIL)->toBeInstanceOf(ExternalCommandRunner::class);
    expect(ExternalCommandRunner::LARAVEL)->toBeInstanceOf(ExternalCommandRunner::class);
});
