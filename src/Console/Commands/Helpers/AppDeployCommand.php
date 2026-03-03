<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Exceptions\AppDeploymentException;
use Illuminate\Contracts\Console\Isolatable;

final class AppDeployCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:deploy {runner : The serve runner to use (herd, sail, laravel)}
        {--s|seed : Seed the database}
        {--m|migrate : Migrate the database}
        {--u|update : Update backend dependencies}';

    protected $description = 'Boot up Laravel environment';

    /**
     * Indicates whether the command should be hidden from the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    public ExternalCommandManager $externalProcessManager;

    public function handleWithInterrupts(): int
    {
        $runner = $this->argument('runner');
        $this->externalProcessManager = new ExternalCommandManager(
            $runner instanceof ExternalCommandRunner ? $runner : ExternalCommandRunner::from($runner),
            $this->output
        );

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🚀 DEPLOYING LARAVEL APPLICATION');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            app(\Illuminate\Pipeline\Pipeline::class)
                ->send($this)
                ->through([
                    \Igne\LaravelBootstrap\Pipelines\Deploy\InstallComposerDependencies::class,
                    \Igne\LaravelBootstrap\Pipelines\Deploy\InstallFrontendDependencies::class,
                    \Igne\LaravelBootstrap\Pipelines\Deploy\RunCustomCommandsBeforeMigrations::class,
                    \Igne\LaravelBootstrap\Pipelines\Deploy\RunDatabaseMigrations::class,
                    \Igne\LaravelBootstrap\Pipelines\Deploy\RunCustomCommandsAfterMigrations::class,
                    \Igne\LaravelBootstrap\Pipelines\Deploy\CacheFrameworkFiles::class,
                    \Igne\LaravelBootstrap\Pipelines\Deploy\StartQueueWorker::class,
                ])
                ->then(function (InterruptibleCommand $command) {
                    $command->finalizeRuntime();
                    $this->info('✅ Laravel booted successfully.');
                    return $command;
                });
        } catch (\Throwable $e) {
            throw new AppDeploymentException($e->getMessage(), \is_int($e->getCode()) ? $e->getCode() : 0, $e);
        }

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Stopping all processes...');
        $this->externalProcessManager->stopAllProcesses();
    }

    public function finalizeRuntime(): self
    {
        $this->info('Clearing auth resets');
        try {
            $this->callSilent('auth:clear-resets');
        } catch (\Throwable $e) {
            $this->warn('Failed to clear auth resets: ' . $e->getMessage());
        }

        $this->info('Linking storage');
        try {
            $this->callSilent('storage:link');
        } catch (\Throwable $e) {
            $this->warn('Failed to link storage: ' . $e->getMessage());
        }

        return $this;
    }
}
