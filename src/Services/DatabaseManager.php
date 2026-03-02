<?php

namespace Igne\LaravelBootstrap\Services;

use Illuminate\Support\Facades\DB;

final class DatabaseManager
{
    public function databaseExists(string $database): bool
    {
        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            if ($driver === 'mysql') {
                $result = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$database]);
                return !empty($result);
            }

            if ($driver === 'pgsql') {
                $result = DB::select("SELECT datname FROM pg_database WHERE datname = ?", [$database]);
                return !empty($result);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createDatabase(string $database): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        if ($driver === 'mysql') {
            $dsn = "mysql:host={$host};port={$port}";
            $pdo = new \PDO($dsn, $username, $password);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } elseif ($driver === 'pgsql') {
            $dsn = "pgsql:host={$host};port={$port}";
            $pdo = new \PDO($dsn, $username, $password);
            $pdo->exec("CREATE DATABASE \"{$database}\" WITH ENCODING 'UTF8'");
        } else {
            throw new \RuntimeException("Unsupported database driver: {$driver}");
        }
    }

    public function updateEnvCredentials(array $credentials): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            throw new \RuntimeException('.env file not found');
        }

        $envContent = file_get_contents($envPath);

        foreach ($credentials as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
