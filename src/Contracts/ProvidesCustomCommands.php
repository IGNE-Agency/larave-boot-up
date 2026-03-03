<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Contracts;

interface ProvidesCustomCommands
{
    /**
     * @return array<\Igne\LaravelBootstrap\Data\DTOs\BootstrapCommand>
     */
    public function getCommands(ProvidesBootstrapCommands $provider): array;

    public function getInfoMessage(): string;
}
