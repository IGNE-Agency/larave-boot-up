<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\HasRuntimeFinalization;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Enums\ProviderOption;
use Igne\LaravelBootstrap\Exceptions\ApplicationDeploymentException;
use Illuminate\Contracts\Console\Isolatable;

final class DeployCommand extends InterruptibleCommand implements HasRuntimeFinalization, Isolatable
{
    protected $signature = 'app:deploy {server : The development server to use (herd, sail, laravel)}
        {--s|seed : Seed the database}
        {--m|migrate : Migrate the database}
        {--u|update : Update backend dependencies}';

    protected $description = 'Boot up Laravel server';

    protected $hidden = true;

    public ExternalCommandManager $externalProcessManager;

    public function handleWithInterrupts(): int
    {
        $server = $this->argument('server');
        $this->externalProcessManager = new ExternalCommandManager(
            $server instanceof DevServerOption ? $server : DevServerOption::from($server),
            $this->output
        );

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🚀 DEPLOYING LARAVEL APPLICATION');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            $bootstrap = $this->laravel->make(ProviderOption::APP_DEPLOY->value);
            $bootstrap($this);
            $this->info('✅ Laravel booted successfully.');
        } catch (\Throwable $e) {
            throw new ApplicationDeploymentException($e->getMessage(), \is_int($e->getCode()) ? $e->getCode() : 0, $e);
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
            $this->warn('Failed to clear auth resets: '.$e->getMessage());
        }

        $this->info('Linking storage');
        try {
            $this->callSilent('storage:link');
        } catch (\Throwable $e) {
            $this->warn('Failed to link storage: '.$e->getMessage());
        }

        return $this;
    }
}
