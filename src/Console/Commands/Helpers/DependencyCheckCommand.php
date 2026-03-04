<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Illuminate\Contracts\Console\Isolatable;

final class DependencyCheckCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'check:dependencies {server : The development environment to use (herd, sail, laravel)}';

    protected $description = 'Make sure the dependencies is correct for development';

    /**
     * Indicates whether the command should be hidden from the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    public function handleWithInterrupts(): int
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📋 CHECKING DEPENDENCIES');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            app(\Illuminate\Pipeline\Pipeline::class)
                ->send($this)
                ->through([
                    \Igne\LaravelBootstrap\Pipelines\Dependencies\ValidateServerServices::class,
                    \Igne\LaravelBootstrap\Pipelines\Dependencies\EnsureEnvFileExists::class,
                    \Igne\LaravelBootstrap\Pipelines\Dependencies\GenerateAppKey::class,
                    \Igne\LaravelBootstrap\Pipelines\Dependencies\ValidateTools::class,
                ])
                ->then(function (InterruptibleCommand $command) {
                    $this->info('✅ All dependencies are correct.');

                    return $command;
                });
        } catch (\Throwable $e) {
            throw new DependencyCheckException($e->getMessage(), \is_int($e->getCode()) ? $e->getCode() : 0, $e);
        }

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up dependency check...');
        $this->externalProcessManager->stopAllProcesses();
    }
}
