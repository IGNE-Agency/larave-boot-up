<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Handlers;

use Igne\LaravelBootstrap\Exceptions\AppDeploymentException;
use Igne\LaravelBootstrap\Exceptions\DatabaseCheckException;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Igne\LaravelBootstrap\Exceptions\ServeException;
use Illuminate\Console\OutputStyle;

final class ErrorHandler
{
    public function __construct(
        private readonly OutputStyle $output
    ) {
    }

    public function handleBootstrapException(\Throwable $exception, string $runnerName): void
    {
        $this->output->error($exception->getMessage());

        $message = $this->getErrorMessage($exception, $runnerName);
        $this->output->error($message);
    }

    private function getErrorMessage(\Throwable $exception, string $runnerName): string
    {
        return match (get_class($exception)) {
            DependencyCheckException::class => 'Failed on the dependencies',
            DatabaseCheckException::class => 'Failed on the database',
            AppDeploymentException::class => 'Failed to deploy Laravel application',
            ServeException::class => "Failed to start the server {$runnerName}",
            default => 'An unexpected error occurred',
        };
    }
}
