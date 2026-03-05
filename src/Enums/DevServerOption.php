<?php

namespace Igne\LaravelBootstrap\Enums;

use Illuminate\Console\Command;

enum DevServerOption: string
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

    public function tips(Command $command): void
    {
        match ($this) {
            self::SAIL => (function () use ($command) {
                $command->info('Tip: Add an alias to your shell:');
                $command->line("   `alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'`");
                $command->line('   Then run `source ~/.zshrc` (or equivalent for your shell)');
                $command->info('Run commands with Sail like: `sail artisan migrate`');
            })(),
            default => null
        };
    }
}
