<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Exceptions\DatabaseCheckException;
use Igne\LaravelBootstrap\Services\DatabaseManager;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\DB;

final class DatabaseCheckCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'check:database {runner : The runner to use (herd, sail, laravel)}';

    protected $description = 'Make sure the database is correct for development';

    protected $hidden = true;

    protected ExternalCommandRunner $runner;

    protected DatabaseManager $databaseManager;

    public function __construct()
    {
        parent::__construct();
        $this->databaseManager = new DatabaseManager();
    }

    public function handleWithInterrupts(): int
    {
        $runner = $this->argument('runner');
        $this->runner = $runner instanceof ExternalCommandRunner ? $runner : ExternalCommandRunner::from($runner ?? 'herd');

        $this->info('Checking database...');
        try {
            $this->checkDatabaseSetup()
                ->ensureDatabaseExists()
                ->isMysqlRunning()
                ->hasDatabaseTables();
        } catch (\Throwable $e) {
            throw new DatabaseCheckException($e->getMessage(), $e->getCode(), $e);
        }
        $this->info('Database setup is correct.');

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up database check...');
        $this->command->stopAllProcesses();
    }

    protected function checkDatabaseSetup(): self
    {
        $missing = collect([
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => config('database.connections.'.config('database.default').'.host'),
            'DB_PORT' => config('database.connections.'.config('database.default').'.port'),
            'DB_DATABASE' => config('database.connections.'.config('database.default').'.database'),
        ])->filter(fn ($value) => $value === null);

        if ($missing->isNotEmpty()) {
            if (config('bootstrap.database.prompt_credentials', true)) {
                $this->promptForDatabaseCredentials();
            } else {
                $missingKeys = $missing->keys()->implode(', ');
                throw new DatabaseCheckException("Database connection is not set up correctly. Missing {$missingKeys}. Please check your .env file.");
            }
        }

        $dbHost = config('database.connections.'.config('database.default').'.host');

        if ($this->runner === ExternalCommandRunner::SAIL && $dbHost !== '127.0.0.1') {
            throw new DatabaseCheckException('Database host is not set to 127.0.0.1 needed for Sail. Please check your .env file.');
        }

        if ($this->runner !== ExternalCommandRunner::SAIL && $dbHost !== '127.0.0.1') {
            throw new DatabaseCheckException('Database host is not set to 127.0.0.1. Please check your .env file.');
        }

        return $this;
    }

    protected function ensureDatabaseExists(): self
    {
        $autoCreate = config('bootstrap.database.auto_create', true);
        
        if (! $autoCreate) {
            return $this;
        }

        $database = config('database.connections.'.config('database.default').'.database');
        
        if (! $this->databaseManager->databaseExists($database)) {
            $this->warn("Database '{$database}' does not exist.");
            
            if ($this->confirm("Would you like to create the database '{$database}'?", true)) {
                $this->databaseManager->createDatabase($database);
                $this->info("Database '{$database}' created successfully.");
            } else {
                throw new DatabaseCheckException("Database '{$database}' does not exist and was not created.");
            }
        }

        return $this;
    }

    protected function isMysqlRunning(): self
    {
        $database = config('database.connections.'.config('database.default').'.database');
        try {
            DB::connection()->getPDO();
            DB::connection()->getDatabaseName();

            return $this;
        } catch (\Throwable $e) {
            throw new DatabaseCheckException(
                'Could not connect to MySQL. This may be due to MySQL not running, incorrect database credentials in your .env file '.
                "(DB_DATABASE, DB_USERNAME, DB_PASSWORD), or the database '{$database}' not existing on the server. ".
                "Please ensure that MySQL is running, the credentials are correct, and that the '{$database}' database exists."
            );
        }
    }

    protected function hasDatabaseTables(): self
    {
        $autoRunMigrations = config('bootstrap.migrations.auto_run', true);
        
        if (! $autoRunMigrations) {
            return $this;
        }

        $tables = DB::connection()->select('SHOW TABLES');

        if (empty($tables)) {
            $this->info('No database tables found. Running migrations...');
            $this->call('migrate', ['--force' => true]);
        }

        return $this;
    }

    protected function promptForDatabaseCredentials(): void
    {
        $this->warn('Database credentials are missing. Please provide them:');
        
        $connection = $this->choice('Database connection type', ['mysql', 'pgsql', 'sqlite'], 0);
        $host = $this->ask('Database host', '127.0.0.1');
        $port = $this->ask('Database port', $connection === 'mysql' ? '3306' : '5432');
        $database = $this->ask('Database name', 'laravel');
        $username = $this->ask('Database username', 'root');
        $password = $this->secret('Database password');

        $this->databaseManager->updateEnvCredentials([
            'DB_CONNECTION' => $connection,
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ]);

        $this->info('Database credentials updated in .env file.');
        
        $this->call('config:clear');
    }
}
