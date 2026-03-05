<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;

final readonly class CacheFrameworkFiles
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if (! config('bootstrap.deploy.enable_caching', true)) {
            return $next($command);
        }

        $command->info('Caching framework files...');

        $this->cacheConfig($command);
        $this->cacheRoutes($command);
        $this->cacheViews($command);

        return $next($command);
    }

    private function cacheConfig(InterruptibleCommand $command): void
    {
        try {
            $command->call('config:cache');
        } catch (\Throwable $e) {
            $command->warn('Failed to cache config: '.$e->getMessage());
        }
    }

    private function cacheRoutes(InterruptibleCommand $command): void
    {
        try {
            $command->call('route:cache');
        } catch (\Throwable $e) {
            $command->warn('Failed to cache routes: '.$e->getMessage());
        }
    }

    private function cacheViews(InterruptibleCommand $command): void
    {
        try {
            $command->call('view:cache');
        } catch (\Throwable $e) {
            $command->warn('Failed to cache views: '.$e->getMessage());
        }
    }
}
