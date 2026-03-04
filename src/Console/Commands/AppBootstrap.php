<?php

namespace Igne\LaravelBootstrap\Console\Commands;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\ServeApplication;
use Igne\LaravelBootstrap\Handlers\ErrorHandler;
use Igne\LaravelBootstrap\Factories\RunnerFactory;
use Igne\LaravelBootstrap\Resolvers\RunnerResolver;
use Illuminate\Contracts\Console\Isolatable;

final class AppBootstrap extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:serve {runner? : The serve runner to use (herd, sail, laravel)}
        {--s|seed : Seed the database}
        {--m|migrate=true : Migrate the database}
        {--u|update : Update dependencies}
        {--no-frontend : Skip frontend setup}';

    protected $description = 'Bootstrap the application environment and serve locally';

    protected Serve $environment;

    public function handleWithInterrupts(): int
    {
        $this->displayStartMessage();

        $environmentName = $this->determineEnvironment();
        $this->environment = $this->createEnvironment($environmentName);

        if (!$this->bootApplication()) {
            return self::FAILURE;
        }

        $this->displaySuccessMessages($environmentName);

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->environment?->cleanup();
        $this->call('app:down');
    }

    private function displayStartMessage(): void
    {
        $this->info('Starting app...');
        $this->newLine(1);
    }

    private function determineEnvironment(): string
    {
        $resolver = new RunnerResolver();
        return $resolver->determineRunner($this->argument('runner'));
    }

    private function createEnvironment(string $environmentName): Serve
    {
        $factory = new RunnerFactory();
        return $factory->create($environmentName, $this);
    }

    private function bootApplication(): bool
    {
        $bootstrapper = new ServeApplication($this->environment);

        try {
            $bootstrapper->boot();
            return true;
        } catch (\Throwable $e) {
            $this->handleBootstrapError($e);
            return false;
        }
    }

    private function handleBootstrapError(\Throwable $exception): void
    {
        $errorHandler = new ErrorHandler($this->output);
        $errorHandler->handleBootstrapException($exception, $this->environment->getRunner()->value);
    }

    private function displaySuccessMessages(string $environmentName): void
    {
        $this->newLine(2);
        $this->info("You can stop the server with php artisan app:down");

        if ($this->environment->getRunner() === ExternalCommandRunner::SAIL) {
            $this->displaySailTips();
        }
    }

    private function displaySailTips(): void
    {
        $this->info('Tip: Add an alias to your shell:');
        $this->line("   `alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'`");
        $this->line('   Then run `source ~/.zshrc` (or equivalent for your shell)');
        $this->info('Run commands with Sail like: `sail artisan migrate`');
    }
}
