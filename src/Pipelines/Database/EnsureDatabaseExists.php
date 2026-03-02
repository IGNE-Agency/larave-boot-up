<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Database;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DatabaseCheckException;
use Igne\LaravelBootstrap\Services\DatabaseManager;

final readonly class EnsureDatabaseExists
{
    public function __construct(
        private DatabaseManager $databaseManager = new DatabaseManager()
    ) {}

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if (! config('bootstrap.database.auto_create', true)) {
            return $next($command);
        }

        $database = config('database.connections.'.config('database.default').'.database');
        
        if (! $this->databaseManager->databaseExists($database)) {
            $command->warn("Database '{$database}' does not exist.");
            
            if ($command->confirm("Would you like to create the database '{$database}'?", true)) {
                $this->databaseManager->createDatabase($database);
                $command->info("Database '{$database}' created successfully.");
            } else {
                throw new DatabaseCheckException("Database '{$database}' does not exist and was not created.");
            }
        }

        return $next($command);
    }
}
