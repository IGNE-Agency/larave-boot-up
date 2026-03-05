<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Bootstrap\DependencyValidationBootstrap;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DependencyValidationException;
use Illuminate\Contracts\Console\Isolatable;

final class ValidateDependenciesCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'check:dependencies {server : The development environment to use (herd, sail, laravel)}';

    protected $description = 'Make sure the dependencies is correct for development';

    protected $hidden = true;

    public function __construct(protected DependencyValidationBootstrap $bootstrapper)
    {
        parent::__construct();
    }

    public function handleWithInterrupts(): int
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📋 CHECKING DEPENDENCIES');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            $this->bootstrapper->register($this)->boot();
            $this->info('✅ All dependencies are correct.');
        } catch (\Throwable $e) {
            throw new DependencyValidationException($e->getMessage(), \is_int($e->getCode()) ? $e->getCode() : 0, $e);
        }

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up dependency check...');
        $this->externalProcessManager->stopAllProcesses();
    }
}
