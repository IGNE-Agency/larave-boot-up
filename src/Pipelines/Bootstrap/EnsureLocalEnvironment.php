<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Runners\ServeRunner;
use Illuminate\Console\Command;

final readonly class EnsureLocalEnvironment
{
    public function handle(Serve $runner, Closure $next): Serve
    {
        $env = app()->environment();

        $allowedEnvironments = ['local', 'development'];

        if (!\in_array($env, $allowedEnvironments, true)) {
            if ($runner instanceof ServeRunner && $runner->console) {
                $runner->console->error("⚠️  This command is for local development only and cannot run in '{$env}' environment.");
                $runner->console->line('');
                $runner->console->line('This package is designed exclusively for local development.');
                $runner->console->line('Please ensure APP_ENV is set to "local" or "development".');
            }

            exit(Command::FAILURE);
        }

        if ($this->isRemoteServer()) {
            if ($runner instanceof ServeRunner && $runner->console) {
                $runner->console->error('⚠️  This command appears to be running on a remote server.');
                $runner->console->line('');
                $runner->console->line('This package is designed exclusively for local development.');
                $runner->console->line('It should not be used on production or staging servers.');
            }

            exit(Command::FAILURE);
        }

        return $next($runner);
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
