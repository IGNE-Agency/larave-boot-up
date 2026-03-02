<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Concerns;

trait ChecksEnvironment
{
    protected function ensureLocalEnvironment(): void
    {
        $env = app()->environment();

        $allowedEnvironments = ['local'];

        if (!\in_array($env, $allowedEnvironments, true)) {
            $this->error("⚠️  This command is for local development only and cannot run in '{$env}' environment.");
            $this->line('');
            $this->line('This package is designed exclusively for local development.');
            $this->line('Please ensure APP_ENV is set to "local".');

            exit(1);
        }

        if ($env !== 'testing' && $this->isRemoteServer()) {
            $this->error('⚠️  This command appears to be running on a remote server.');
            $this->line('');
            $this->line('This package is designed exclusively for local development.');
            $this->line('It should not be used on production or staging servers.');

            exit(1);
        }
    }

    protected function isRemoteServer(): bool
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
