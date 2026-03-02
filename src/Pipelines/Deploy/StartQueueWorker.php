<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Symfony\Component\Console\Command\Command;

final readonly class StartQueueWorker
{
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

    private function canOpenTerminal(): bool
    {
        return OSCommand::OPEN_TERMINAL->canExecute();
    }

    private function startInSeparateTerminal(InterruptibleCommand $command): void
    {
        $terminalCommand = OSCommand::OPEN_TERMINAL
            ->withCommand(new Command('queue:work'))
            ->execute();

        if ($terminalCommand) {
            $command->externalProcessManager->call($terminalCommand);
            $command->info('Queue worker started in separate terminal.');
        }
    }

    private function startInBackground(InterruptibleCommand $command): void
    {
        $queueConnection = config('bootstrap.queue.connection', 'database');
        $command->externalProcessManager->call(['php', 'artisan', 'queue:work', $queueConnection, '--daemon']);
        $command->info('Queue worker started in background.');
    }
}
