<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Handlers;

use Igne\LaravelBootstrap\Exceptions\ApplicationDeploymentException;
use Igne\LaravelBootstrap\Exceptions\DatabaseValidationException;
use Igne\LaravelBootstrap\Exceptions\DependencyValidationException;
use Igne\LaravelBootstrap\Exceptions\ServeException;
use Illuminate\Console\OutputStyle;

final class ErrorHandler
{
    public function __construct(
        private readonly OutputStyle $output
    ) {}

    public function handleBootstrapException(\Throwable $exception, string $serverName): void
    {
        $this->output->error($exception->getMessage());

        $message = $this->getErrorMessage($exception, $serverName);
        $this->output->error($message);
    }

    private function getErrorMessage(\Throwable $exception, string $serverName): string
    {
        return match (get_class($exception)) {
            DependencyValidationException::class => 'Failed on the dependencies',
            DatabaseValidationException::class => 'Failed on the database',
            ApplicationDeploymentException::class => 'Failed to deploy Laravel application',
            ServeException::class => "Failed to start the server {$serverName}",
            default => 'An unexpected error occurred',
        };
    }
}
