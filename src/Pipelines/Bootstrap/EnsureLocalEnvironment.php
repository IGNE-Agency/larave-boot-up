<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Development\DevEnvironmentRunner;
use Illuminate\Console\Command;

final readonly class EnsureLocalEnvironment
{
    public function handle(Serve $environment, Closure $next): Serve
    {
        $env = app()->environment();

        $allowedEnvironments = ['local', 'development'];

        if (!\in_array($env, $allowedEnvironments, true)) {
            if ($environment instanceof DevEnvironmentRunner && $environment->console) {
                $environment->console->error("⚠️  This command is for local development only and cannot run in '{$env}' environment.");
                $environment->console->line('');
                $environment->console->line('This package is designed exclusively for local development.');
                $environment->console->line('Please ensure APP_ENV is set to "local" or "development".');
            }

            exit(Command::FAILURE);
        }

        if ($this->isRemoteServer()) {
            if ($environment instanceof DevEnvironmentRunner && $environment->console) {
                $environment->console->error('⚠️  This command appears to be running on a remote server.');
                $environment->console->line('');
                $environment->console->line('This package is designed exclusively for local development.');
                $environment->console->line('It should not be used on servers.');
            }

            exit(Command::FAILURE);
        }

        return $next($environment);
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
