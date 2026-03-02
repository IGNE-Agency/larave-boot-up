<?php

namespace Igne\LaravelBootstrap\Enums;

enum ExternalCommandRunner: string
{
    case HERD = 'herd';
    case SAIL = 'sail';
    case LARAVEL = 'laravel';

    public function replaces(): array
    {
        return match ($this) {
            self::SAIL => [
                'php artisan' => 'artisan',
            ],
            default => []
        };
    }

    public function prefixes(): array
    {
        return match ($this) {
            self::HERD => ['php', 'composer', 'tinker'],
            self::SAIL => ['php', 'composer', 'yarn', 'npm', 'bun', 'artisan', 'node'],
            default => []
        };
    }

    public function command(): string
    {
        return match ($this) {
            self::HERD => 'herd',
            self::SAIL => './vendor/bin/sail',
            default => ''
        };
    }
}
