<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Contracts;

use Igne\LaravelBootstrap\Data\DTOs\BootstrapCommand;

interface ProvidesBootstrapCommands
{
    /**
     * @return array<BootstrapCommand>
     */
    public function beforeMigrations(): array;

    /**
     * @return array<BootstrapCommand>
     */
    public function afterMigrations(): array;
}
