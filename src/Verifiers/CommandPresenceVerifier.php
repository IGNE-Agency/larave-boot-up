<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Verifiers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

final class CommandPresenceVerifier
{
    public function isAvailable(string $command): bool
    {
        $checkCommand = [...Arr::wrap($command), '-v'];
        $process = new Process($checkCommand, base_path());
        $process->run();

        return $process->isSuccessful();
    }

    public function isRunning(string $command): bool
    {
        $process = new Process(Arr::wrap($command), base_path());
        $process->run();

        return $process->isSuccessful()
            && Str::of($process->getOutput())->trim()->isNotEmpty();
    }
}
