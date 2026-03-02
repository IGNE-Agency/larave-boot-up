<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;

final readonly class RunDatabaseMigrations
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if ($command->hasOption('migrate') && $command->option('migrate')) {
            $command->info('Running database migrations...');
            $command->call('migrate', ['--force' => true]);
        }

        if ($command->hasOption('seed') && $command->option('seed')) {
            $command->info('Seeding database...');
            $command->call('db:seed', ['--force' => true]);
        }

        return $next($command);
    }
}
