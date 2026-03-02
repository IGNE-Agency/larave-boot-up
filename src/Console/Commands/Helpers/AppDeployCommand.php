<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Exceptions\AppDeploymentException;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

final class AppDeployCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:deploy {runner : The serve runner to use (herd, sail, laravel)}
        {--s|seed : Seed the database}
        {--m|migrate : Migrate the database}
        {--u|update : Update backend dependencies}';

    protected $description = 'Boot up Laravel environment';

    protected $hidden = true;

    protected ExternalCommandManager $command;

    protected const DEPENDENCY_COMMANDS = [];

    public function handleWithInterrupts(): int
    {
        $runner = $this->argument('runner');
        $this->command = new ExternalCommandManager(
            $runner instanceof ExternalCommandRunner ? $runner : ExternalCommandRunner::from($runner),
            $this->output
        );

        $this->info('Booting up Laravel...');

        try {
            $this
                ->installDependencies()
                ->runDependencyCommandsIfAvailable()
                ->buildAssets()
                ->runDatabaseSetup()
                ->cacheFrameworkFiles()
                ->startQueueWorker()
                ->finalizeRuntime();
        } catch (\Throwable $e) {
            throw new AppDeploymentException($e->getMessage(), $e->getCode(), $e);
        }

        $this->info('Laravel booted successfully.');

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Stopping all processes...');
        $this->command->stopAllProcesses();
    }

    protected function installDependencies(): self
    {
        if (!file_exists(base_path('composer.json'))) {
            $this->warn('composer.json not found, skipping installing dependencies.');

            return $this;
        }

        if ($this->option('update')) {
            $this->info('Updating dependencies...');
            $this->command->composer('run upgrade');
        }

        $this->info('Installing dependencies...');
        $this->command->composer('install', ['--no-interaction', '--prefer-dist']);

        return $this;
    }

    protected function runDependencyCommandsIfAvailable(): self
    {
        $commands = collect(self::DEPENDENCY_COMMANDS);

        if ($commands->isEmpty()) {
            $this->info('No additional dependency commands to run.');

            return $this;
        }

        $this->info('Running dependency commands...');

        $this->artisanCommands()
            ->pipe(
                fn($availableCommands) =>
                $commands->partition(
                    fn($_, $command) => $availableCommands->contains($command)
                )
            )
            ->tap(
                function ($commands) {
                    [$runnableCommands, $skippedCommands] = $commands;

                    $skippedCommands->each(
                        fn($_, $commands) => $this->warn("Skipped: {$commands} (not available)")
                    );

                    $runnableCommands->each(
                        function ($message, $commands) {
                            $this->line($message);
                            $this->call($commands);
                        }
                    );
                }
            );

        return $this;
    }

    protected function runDatabaseSetup(): self
    {
        if ($this->option('migrate')) {
            $this->info('Running database migrations...');
            $this->call('migrate', ['--force' => true]);
        } else {
            $this->info('Skipping migrations.');
        }

        if ($this->option('seed')) {
            $this->info('Seeding database...');
            $this->call('db:seed', ['--force' => true]);
        } else {
            $this->info('Skipping seeding.');
        }

        return $this;
    }

    protected function cacheFrameworkFiles(): self
    {
        $this->info('Rebuilding Laravel caches...');
        $this->call('optimize:clear');

        return $this;
    }

    public function buildAssets(): self
    {
        if (!file_exists(base_path('package.json'))) {
            $this->warn('package.json not found, skipping frontend setup.');

            return $this;
        }

        if ($this->option('update')) {
            $this->info('Updating dependencies...');
            $pmEnum = $this->command->getPackageManager();
            $this->command->packageManager($pmEnum->updateCommand());
        }

        $pmEnum = $this->command->getPackageManager();

        $this->info('Installing dependencies');
        $this->command->packageManager($pmEnum->installCommand());
        $this->info('Building frontend assets');
        $this->command->packageManager($pmEnum->buildCommand());

        return $this;
    }

    protected function startQueueWorker(): self
    {
        $autoStart = config('bootstrap.queue.auto_start', true);
        $separateTerminal = config('bootstrap.queue.separate_terminal', true);

        if (!$autoStart) {
            return $this;
        }

        $this->info('Starting queue worker...');

        if ($separateTerminal) {
            $this->startQueueInSeparateTerminal();
        } else {
            $this->command->php(['artisan', 'queue:work', '--tries=3']);
        }

        return $this;
    }

    protected function startQueueInSeparateTerminal(): void
    {
        $script = base_path('vendor/bin/queue-worker.sh');

        if (!file_exists($script)) {
            $this->createQueueWorkerScript($script);
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            $this->command->call([
                'osascript',
                '-e',
                'tell application "Terminal" to do script "cd ' . base_path() . ' && php artisan queue:work --tries=3"'
            ]);
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $this->command->call([
                'gnome-terminal',
                '--',
                'bash',
                '-c',
                'cd ' . base_path() . ' && php artisan queue:work --tries=3; exec bash'
            ]);
        } else {
            $this->warn('Separate terminal for queue worker not supported on this OS. Running in background...');
            $this->command->php(['artisan', 'queue:work', '--tries=3']);
        }

        $this->info('Queue worker started in separate terminal.');
    }

    protected function createQueueWorkerScript(string $path): void
    {
        $script = <<<'BASH'
#!/bin/bash
cd "$(dirname "$0")/../.."
php artisan queue:work --tries=3
BASH;

        file_put_contents($path, $script);
        chmod($path, 0755);
    }

    protected function finalizeRuntime(): self
    {
        $this->info('Clearing auth resets');
        $this->call('auth:clear-resets');

        $this->info('Linking storage');
        $this->call('storage:link');

        $this->info('Restarting queues');
        $this->call('queue:restart');

        return $this;
    }

    private function artisanCommands(): Collection
    {
        return collect(Artisan::all())->keys();
    }
}
