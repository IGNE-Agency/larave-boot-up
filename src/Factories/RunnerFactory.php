<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Factories;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Development\HerdDevEnvironment;
use Igne\LaravelBootstrap\Development\LaravelDevEnvironment;
use Igne\LaravelBootstrap\Development\SailDevEnvironment;

final class RunnerFactory
{
    public function create(string $runnerName, InterruptibleCommand $command): Serve
    {
        return match ($runnerName) {
            'herd' => new HerdDevEnvironment($command),
            'sail' => new SailDevEnvironment($command),
            'laravel' => new LaravelDevEnvironment($command),
            default => throw new \InvalidArgumentException("Unknown runner: {$runnerName}")
        };
    }
}
