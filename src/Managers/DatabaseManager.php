<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Managers;

use Igne\LaravelBootstrap\Contracts\ManagesDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class DatabaseManager implements ManagesDatabase
{
    public function databaseExists(string $database): bool
    {
        try {
            $driver = $this->getDriver();

            return match ($driver) {
                'mysql' => $this->queryDatabaseExists("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", $database),
                'pgsql' => $this->queryDatabaseExists("SELECT datname FROM pg_database WHERE datname = ?", $database),
                'sqlite' => $this->sqliteDatabaseExists(),
                'sqlsrv' => $this->queryDatabaseExists("SELECT name FROM sys.databases WHERE name = ?", $database),
                default => false,
            };
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createDatabase(string $database): void
    {
        match ($this->getDriver()) {
            'mysql' => $this->createMySQLDatabase($database),
            'pgsql' => $this->createPostgreSQLDatabase($database),
            'sqlite' => $this->createSQLiteDatabase(),
            'sqlsrv' => $this->createSQLServerDatabase($database),
            default => throw new \RuntimeException("Unsupported database driver: {$this->getDriver()}"),
        };
    }

    public function updateEnvCredentials(array $credentials): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            throw new \RuntimeException('.env file not found');
        }

        $envContent = File::get($envPath);

        foreach ($credentials as $key => $value) {
            $pattern = "/^{$key}=.*/m";

            $envContent = Str::of($envContent)
                ->when(
                    Str::contains($envContent, "{$key}="),
                    fn($str) => $str->replaceMatches($pattern, "{$key}={$value}"),
                    fn($str) => $str->append("\n{$key}={$value}")
                )
                ->toString();
        }

        File::put($envPath, $envContent);
    }

    private function queryDatabaseExists(string $query, string $database): bool
    {
        return !empty(DB::select($query, [$database]));
    }

    private function sqliteDatabaseExists(): bool
    {
        $databasePath = $this->getConnectionConfig('database');
        return $databasePath === ':memory:' || File::exists($databasePath);
    }

    private function createMySQLDatabase(string $database): void
    {
        $pdo = $this->createPDO('mysql');
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function createPostgreSQLDatabase(string $database): void
    {
        $pdo = $this->createPDO('pgsql');

        if (!$this->pdoDatabaseExists($pdo, "SELECT 1 FROM pg_database WHERE datname = '{$database}'")) {
            $pdo->exec("CREATE DATABASE \"{$database}\" WITH ENCODING 'UTF8'");
        }
    }

    private function createSQLiteDatabase(): void
    {
        $databasePath = $this->getConnectionConfig('database');

        if ($databasePath === ':memory:' || File::exists($databasePath)) {
            return;
        }

        File::ensureDirectoryExists(dirname($databasePath), 0755, true);
        touch($databasePath);
    }

    private function createSQLServerDatabase(string $database): void
    {
        $pdo = $this->createPDO('sqlsrv');

        if (!$this->pdoDatabaseExists($pdo, "SELECT name FROM sys.databases WHERE name = '{$database}'")) {
            $pdo->exec("CREATE DATABASE [{$database}] COLLATE Latin1_General_100_CI_AS_SC_UTF8");
        }
    }

    private function getDriver(): string
    {
        return $this->getConnectionConfig('driver');
    }

    private function getConnectionConfig(string $key): mixed
    {
        $connection = config('database.default');
        return config("database.connections.{$connection}.{$key}");
    }

    private function createPDO(string $driver): \PDO
    {
        $dsn = match ($driver) {
            'mysql' => "mysql:host={$this->getConnectionConfig('host')};port={$this->getConnectionConfig('port')}",
            'pgsql' => "pgsql:host={$this->getConnectionConfig('host')};port={$this->getConnectionConfig('port')}",
            'sqlsrv' => "sqlsrv:Server={$this->getConnectionConfig('host')},{$this->getConnectionConfig('port')}",
        };

        return new \PDO($dsn, $this->getConnectionConfig('username'), $this->getConnectionConfig('password'));
    }

    private function pdoDatabaseExists(\PDO $pdo, string $query): bool
    {
        $result = $pdo->query($query);
        return $result && $result->rowCount() > 0;
    }
}
