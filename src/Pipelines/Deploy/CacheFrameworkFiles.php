<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;

final readonly class CacheFrameworkFiles
{
    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        if (!config('bootstrap.deploy.enable_caching', true)) {
            return $next($command);
        }

        $command->info('Caching framework files...');

        $command->call('config:cache');
        $command->call('route:cache');
        $command->call('view:cache');

        return $next($command);
    }
}
