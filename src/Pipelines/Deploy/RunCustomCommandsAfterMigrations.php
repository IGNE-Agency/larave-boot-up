<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Igne\LaravelBootstrap\Contracts\ProvidesBootstrapCommands;

final readonly class RunCustomCommandsAfterMigrations extends RunCustomCommands
{
    public function getCommands(ProvidesBootstrapCommands $provider): array
    {
        return $provider->afterMigrations();
    }

    public function getInfoMessage(): string
    {
        return 'Running custom commands after migrations...';
    }
}
