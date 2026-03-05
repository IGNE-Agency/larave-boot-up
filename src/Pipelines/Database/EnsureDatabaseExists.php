<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Database;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DatabaseValidationException;
use Igne\LaravelBootstrap\Managers\DatabaseManager;

use function Laravel\Prompts\confirm;

final readonly class EnsureDatabaseExists
{
    public function __construct(
        private DatabaseManager $databaseManager = new DatabaseManager
    ) {}

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if (! config('bootstrap.database.auto_create', true)) {
            return $next($command);
        }

        $database = config('database.connections.'.config('database.default').'.database');

        if (! $this->databaseManager->databaseExists($database)) {
            $command->warn("Database '{$database}' does not exist.");
            $command->newLine();

            if (
                confirm(
                    label: "Would you like to create the database '{$database}'?",
                    default: true,
                    yes: 'Create database',
                    no: 'Skip creation',
                    hint: 'The database will be created automatically'
                )
            ) {
                $this->databaseManager->createDatabase($database);
                $command->info("Database '{$database}' created successfully.");
            } else {
                throw new DatabaseValidationException("Database '{$database}' does not exist and was not created.");
            }
        }

        return $next($command);
    }
}
