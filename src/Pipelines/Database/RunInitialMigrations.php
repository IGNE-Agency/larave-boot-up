<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Database;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Illuminate\Support\Facades\DB;

final readonly class RunInitialMigrations
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if (! config('bootstrap.migrations.auto_run', true)) {
            return $next($command);
        }

        $tables = DB::connection()->select('SHOW TABLES');

        if (empty($tables)) {
            $command->info('No database tables found. Running migrations...');
            $command->call('migrate', ['--force' => true]);
        }

        return $next($command);
    }
}
