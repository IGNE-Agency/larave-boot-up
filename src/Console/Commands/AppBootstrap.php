<?php

namespace Igne\LaravelBootstrap\Console\Commands;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Server;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Factories\ServerFactory;
use Igne\LaravelBootstrap\Handlers\ErrorHandler;
use Igne\LaravelBootstrap\Resolvers\ServerResolver;
use Igne\LaravelBootstrap\ServeApplication;
use Illuminate\Contracts\Console\Isolatable;

final class AppBootstrap extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:serve {server? : The development server to use (herd, sail, laravel)}
        {--s|seed : Seed the database}
        {--m|migrate=true : Migrate the database}
        {--u|update : Update dependencies}
        {--no-frontend : Skip frontend setup}';

    protected $description = 'Bootstrap the application server and serve locally';

    protected Server $server;

    public function handleWithInterrupts(): int
    {
        $this->displayStartMessage();

        $serverName = $this->determineServer();
        $this->server = $this->createServer($serverName);

        if (! $this->bootApplication()) {
            return self::FAILURE;
        }

        $this->displaySuccessMessages($serverName);

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->server?->cleanup();
        $this->call('app:down');
    }

    private function displayStartMessage(): void
    {
        $this->info('Starting app...');
        $this->newLine(1);
    }

    private function determineServer(): string
    {
        $resolver = new ServerResolver;

        return $resolver->determineServer($this->argument('server'));
    }

    private function createServer(string $serverName): Server
    {
        $factory = new ServerFactory;

        return $factory->create($serverName, $this);
    }

    private function bootApplication(): bool
    {
        $bootstrapper = new ServeApplication($this->server);

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
        $errorHandler->handleBootstrapException($exception, $this->server->getServer()->value);
    }

    private function displaySuccessMessages(string $serverName): void
    {
        $this->newLine(2);
        $this->info('You can stop the server with php artisan app:down');

        if ($this->server->getServer() === DevServerOption::SAIL) {
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
