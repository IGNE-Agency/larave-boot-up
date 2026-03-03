<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DatabaseCheckException;
use Illuminate\Contracts\Console\Isolatable;

final class DatabaseCheckCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'check:database {runner : The runner to use (herd, sail, laravel)}';

    protected $description = 'Make sure the database is correct for development';

    /**
     * Indicates whether the command should be hidden from the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    public function handleWithInterrupts(): int
    {
        $this->info('Checking database...');

        try {
            app(\Illuminate\Pipeline\Pipeline::class)
                ->send($this)
                ->through([
                    \Igne\LaravelBootstrap\Pipelines\Database\CheckDatabaseSetup::class,
                    \Igne\LaravelBootstrap\Pipelines\Database\EnsureDatabaseExists::class,
                    \Igne\LaravelBootstrap\Pipelines\Database\VerifyDatabaseConnection::class,
                    \Igne\LaravelBootstrap\Pipelines\Database\RunInitialMigrations::class,
                ])
                ->finally(function () {
                    $this->info('Database setup is correct.');
                })
                ->thenReturn();
        } catch (\Throwable $e) {
            throw new DatabaseCheckException($e->getMessage(), $e->getCode(), $e);
        }

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up database check...');
        $this->externalProcessManager->stopAllProcesses();
    }
}
