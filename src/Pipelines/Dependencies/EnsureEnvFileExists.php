<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Dependencies;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Illuminate\Support\Facades\File;

final readonly class EnsureEnvFileExists
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $env = base_path('.env');
        $example = base_path('.env.example');

        if (! File::exists($env) && File::exists($example)) {
            File::copy($example, $env);
            $command->info('.env copied from .env.example');
        } elseif (File::exists($env)) {
            $command->info('.env already exists, skipping.');
        } else {
            throw new DependencyCheckException('No .env or .env.example found. Please create one.');
        }

        return $next($command);
    }
}
