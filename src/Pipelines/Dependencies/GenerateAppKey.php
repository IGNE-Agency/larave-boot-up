<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Dependencies;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;

final readonly class GenerateAppKey
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if (empty(config('app.key')) || config('app.key') === 'base64:') {
            $command->info('Generating application key...');
            $command->call('key:generate', ['--force' => true]);
        }

        return $next($command);
    }
}
