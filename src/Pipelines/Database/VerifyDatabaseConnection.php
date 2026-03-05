<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Database;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DatabaseCheckException;
use Illuminate\Support\Facades\DB;

final readonly class VerifyDatabaseConnection
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $database = config('database.connections.'.config('database.default').'.database');

        try {
            DB::connection()->getPDO();
            DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            throw new DatabaseCheckException(
                'Could not connect to MySQL. This may be due to MySQL not running, incorrect database credentials in your .env file '.
                "(DB_DATABASE, DB_USERNAME, DB_PASSWORD), or the database '{$database}' not existing on the server. ".
                "Please ensure that MySQL is running, the credentials are correct, and that the '{$database}' database exists."
            );
        }

        return $next($command);
    }
}
