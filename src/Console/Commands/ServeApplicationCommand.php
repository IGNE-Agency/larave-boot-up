<?php

namespace Igne\LaravelBootstrap\Console\Commands;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\Server;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Enums\ProviderOption;
use Igne\LaravelBootstrap\Factories\ServerFactory;
use Igne\LaravelBootstrap\Handlers\ErrorHandler;
use Igne\LaravelBootstrap\Resolvers\ServerResolver;
use Illuminate\Contracts\Console\Isolatable;

final class ServeApplicationCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:serve {server? : The development server to use (herd, sail, laravel)}
        {--s|seed : Seed the database}
        {--m|migrate=true : Migrate the database}
        {--u|update : Update dependencies}
        {--no-frontend : Skip frontend setup}';

    protected $description = 'Bootstrap the application server and serve locally';

    protected Server $server;

    public function __construct(
        private ServerResolver $serverResolver,
        private ServerFactory $serverFactory
    ) {
        parent::__construct();
    }

    public function handleWithInterrupts(): int
    {
        $this->displayStartMessage();

        $serverOption = $this->determineServer();
        $this->server = $this->createServer($serverOption);

        if (! $this->bootApplication()) {
            return self::FAILURE;
        }

        $this->displaySuccessMessages();

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->server->cleanup();
        $this->call('app:down');
    }

    private function displayStartMessage(): void
    {
        $this->info('Starting app...');
        $this->newLine(1);
    }

    private function determineServer(): DevServerOption
    {
        return $this->serverResolver->determineServer($this->argument('server'));
    }

    private function createServer(DevServerOption $serverOption): Server
    {
        return $this->serverFactory->create($serverOption, $this);
    }

    private function bootApplication(): bool
    {
        try {
            $bootstrap = $this->laravel->make(ProviderOption::APP_SERVE->value);
            $bootstrap($this->server);

            return true;
        } catch (\Throwable $e) {
            $this->handleBootstrapError($e);

            return false;
        }
    }

    private function handleBootstrapError(\Throwable $exception): void
    {
        $errorHandler = new ErrorHandler($this->output);
        $errorHandler->handleBootstrapException($exception, $this->server->getServer());
    }

    private function displaySuccessMessages(): void
    {
        $this->newLine(2);
        $this->info('You can stop the server with php artisan app:down');

        $this->server->getServer()->tips($this);
    }
}
