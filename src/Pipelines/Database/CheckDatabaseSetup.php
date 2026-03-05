<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Database;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Exceptions\DatabaseValidationException;
use Igne\LaravelBootstrap\Managers\DatabaseManager;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

final readonly class CheckDatabaseSetup
{
    public function __construct(
        private DatabaseManager $databaseManager = new DatabaseManager
    ) {}

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $missing = collect([
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => config('database.connections.'.config('database.default').'.host'),
            'DB_PORT' => config('database.connections.'.config('database.default').'.port'),
            'DB_DATABASE' => config('database.connections.'.config('database.default').'.database'),
        ])->filter(fn ($value) => $value === null);

        if ($missing->isNotEmpty()) {
            $this->handleMissingCredentials($command, $missing);
        }

        $this->validateDatabaseHost($command);

        return $next($command);
    }

    private function handleMissingCredentials(InterruptibleCommand $command, $missing): void
    {
        if (config('bootstrap.database.prompt_credentials', true)) {
            $this->promptForDatabaseCredentials($command);
        } else {
            $missingKeys = $missing->keys()->implode(', ');
            throw new DatabaseValidationException("Database connection is not set up correctly. Missing {$missingKeys}. Please check your .env file.");
        }
    }

    private function validateDatabaseHost(InterruptibleCommand $command): void
    {
        $serverArgument = $command->argument('server');
        $serverOption = $serverArgument instanceof DevServerOption ? $serverArgument : DevServerOption::from($serverArgument ?? config('bootstrap.server.default', 'herd'));
        $dbHost = config(key: 'database.connections.'.config('database.default').'.host');

        if ($serverOption === DevServerOption::SAIL && $dbHost !== '127.0.0.1') {
            throw new DatabaseValidationException('Database host is not set to 127.0.0.1 needed for Sail. Please check your .env file.');
        }

        if ($serverOption !== DevServerOption::SAIL && $dbHost !== '127.0.0.1') {
            throw new DatabaseValidationException('Database host is not set to 127.0.0.1. Please check your .env file.');
        }
    }

    private function promptForDatabaseCredentials(InterruptibleCommand $command): void
    {
        $command->warn('Database credentials are missing. Please provide them:');
        $command->newLine();

        $connection = select(
            label: 'Database connection type',
            options: [
                'mysql' => 'MySQL',
                'pgsql' => 'PostgreSQL',
                'sqlite' => 'SQLite',
            ],
            default: env('DB_CONNECTION', 'mysql')
        );

        $host = text(
            label: 'Database host',
            default: env('DB_HOST', '127.0.0.1'),
            required: true
        );

        $port = text(
            label: 'Database port',
            default: env('DB_PORT', $connection === 'mysql' ? '3306' : '5432'),
            required: true
        );

        $database = text(
            label: 'Database name',
            default: env('DB_DATABASE', str(config('app.name') ?? 'laravel')->slug()->toString()),
            required: true
        );

        $username = text(
            label: 'Database username',
            default: env('DB_USERNAME', 'root'),
            required: true
        );

        $password = password(
            label: 'Database password',
            required: false
        );

        $this->databaseManager->updateEnvCredentials([
            'DB_CONNECTION' => $connection,
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ]);

        $command->info('Database credentials updated in .env file.');
        $command->call('config:clear');
    }
}
