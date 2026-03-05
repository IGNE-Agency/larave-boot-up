<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Bootstrap\DatabaseSetupBootstrap;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DatabaseValidationException;
use Illuminate\Contracts\Console\Isolatable;

final class ValidateDatabaseCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'check:database {server : The development server to use (herd, sail, laravel)}';

    protected $description = 'Make sure the database is correct for development';

    protected $hidden = true;

    public function __construct(protected DatabaseSetupBootstrap $bootstrapper)
    {
        parent::__construct();
    }

    public function handleWithInterrupts(): int
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🗄️  CHECKING DATABASE');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            $this->bootstrapper->register($this)->boot();
            $this->info('✅ Database setup is correct.');
        } catch (\Throwable $e) {
            throw new DatabaseValidationException($e->getMessage(), \is_int($e->getCode()) ? $e->getCode() : 0, $e);
        }

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up database check...');
        $this->externalProcessManager->stopAllProcesses();
    }
}
