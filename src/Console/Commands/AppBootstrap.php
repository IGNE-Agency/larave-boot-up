<?php

namespace Igne\LaravelBootstrap\Console\Commands;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Exceptions\AppDeploymentException;
use Igne\LaravelBootstrap\Exceptions\DatabaseCheckException;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Igne\LaravelBootstrap\Exceptions\ServeException;
use Igne\LaravelBootstrap\Runners\ServeHerdRunner;
use Igne\LaravelBootstrap\Runners\ServeLaravelRunner;
use Igne\LaravelBootstrap\Runners\ServeSailRunner;
use Igne\LaravelBootstrap\ServeApplication;
use Illuminate\Contracts\Console\Isolatable;

use function Laravel\Prompts\select;

final class AppBootstrap extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:serve {runner? : The serve runner to use (herd, sail, laravel)}
        {--s|seed : Seed the database}
        {--m|migrate=true : Migrate the database}
        {--u|update : Update dependencies}
        {--no-frontend : Skip frontend setup}';

    protected $description = 'Bootstrap the application environment and serve locally';

    protected Serve $runner;

    public function handleWithInterrupts(): int
    {
        $this->info('Starting app...');
        $this->newLine(1);

        $runnerName = $this->determineRunner();
        $this->runner = $this->createRunner($runnerName);

        $bootstrapper = new ServeApplication($this->runner);

        try {
            $bootstrapper->boot();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            switch (get_class($e)) {
                case DependencyCheckException::class:
                    $this->fail('Failed on the dependencies');
                    break;
                case DatabaseCheckException::class:
                    $this->fail('Failed on the database');
                    break;
                case AppDeploymentException::class:
                    $this->fail('Failed to deploy Laravel application');
                    break;
                case ServeException::class:
                    $this->fail("Failed to start the server {$runnerName}");
                    break;
                default:
                    $this->fail('An unexpected error occurred');
                    break;
            }

            return self::FAILURE;
        }
        $this->newLine(2);

        $this->info("You can stop the server with php artisan app:down");

        if ($this->runner->getRunner() === ExternalCommandRunner::SAIL) {
            $this->info('Tip: Add an alias to your shell:');
            $this->line("   `alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'`");
            $this->line('   Then run `source ~/.zshrc` (or equivalent for your shell)');
            $this->info('Run commands with Sail like: `sail artisan migrate`');
        }

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->runner?->cleanup();
        $this->call('app:down');
    }

    protected function determineRunner(): string
    {
        $runnerArg = $this->argument('runner');

        if ($runnerArg) {
            return strtolower($runnerArg);
        }

        $defaultRunner = config('bootstrap.runner.default');
        $shouldPrompt = config('bootstrap.runner.prompt', true);

        if ($defaultRunner && !$shouldPrompt) {
            return strtolower($defaultRunner);
        }

        return $this->promptForRunner();
    }

    protected function promptForRunner(): string
    {
        $choice = select(
            label: 'Select your development environment',
            options: [
                'herd' => 'Laravel Herd - Fast local development with PHP and Nginx',
                'sail' => 'Laravel Sail - Docker-based development environment',
                'laravel' => 'Laravel Artisan - Built-in PHP development server',
            ],
            default: 'herd'
        );

        return $choice;
    }

    protected function createRunner(string $runnerName): Serve
    {
        return match ($runnerName) {
            'herd' => new ServeHerdRunner($this),
            'sail' => new ServeSailRunner($this),
            'laravel' => new ServeLaravelRunner($this),
            default => throw new \InvalidArgumentException("Unknown runner: {$runnerName}")
        };
    }
}
