<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Traits\OpensTerminalCommands;

final readonly class StartQueueWorker
{
    use OpensTerminalCommands;
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $autoStart = config('bootstrap.queue.auto_start', true);
        $separateTerminal = config('bootstrap.queue.separate_terminal', true);

        if (!$autoStart) {
            $command->info('Queue worker auto-start disabled.');

            return $next($command);
        }

        $command->info('Starting queue worker...');

        if ($separateTerminal && $this->canOpenTerminal()) {
            $this->startInSeparateTerminal($command);
        } else {
            $this->startInBackground($command);
        }

        return $next($command);
    }

    private function startInSeparateTerminal(InterruptibleCommand $command): void
    {
        $this->executeInSeparateTerminal('php artisan queue:work');
        $command->info('Queue worker started in separate terminal.');
    }

    private function startInBackground(InterruptibleCommand $command): void
    {
        $command->call('queue:work');
        $command->info('Queue worker started in background.');
    }
}
