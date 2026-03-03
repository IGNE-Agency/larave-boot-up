<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Contracts;

interface ManagesDatabase
{
    public function databaseExists(string $database): bool;

    public function createDatabase(string $database): void;

    public function updateEnvCredentials(array $credentials): void;
}
