<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Server;
use Igne\LaravelBootstrap\Servers\DevServer;
use Illuminate\Console\Command;

final readonly class EnsureLocalEnvironment
{
    public function handle(Server $server, Closure $next): Server
    {
        $env = app()->environment();

        $allowedEnvironments = ['local', 'development'];

        if (!\in_array($env, $allowedEnvironments, true)) {
            if ($server instanceof DevServer && $server->console) {
                $server->console->error("⚠️  This command is for local development only and cannot run in '{$env}' environment.");
                $server->console->line('');
                $server->console->line('This package is designed exclusively for local development.');
                $server->console->line('Please ensure APP_ENV is set to "local" or "development".');
            }

            exit(Command::FAILURE);
        }

        if ($this->isRemoteServer()) {
            if ($server instanceof DevServer && $server->console) {
                $server->console->error('⚠️  This command appears to be running on a remote server.');
                $server->console->line('');
                $server->console->line('This package is designed exclusively for local development.');
                $server->console->line('It should not be used on servers.');
            }

            exit(Command::FAILURE);
        }

        return $next($server);
    }

    private function isRemoteServer(): bool
    {
        $indicators = [
            isset($_SERVER['SSH_CONNECTION']),
            isset($_SERVER['SSH_CLIENT']),
            isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1',
            php_sapi_name() === 'fpm-fcgi',
        ];

        return \in_array(true, $indicators, true);
    }
}
