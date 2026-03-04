<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Resolvers;

use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Parsers\CommandParser;
use Igne\LaravelBootstrap\Traits\BuildsCommandOptions;
use Illuminate\Support\Collection;

final class CommandResolver
{
    use BuildsCommandOptions;

    public function __construct(
        private readonly CommandParser $parser,
        private readonly ?DevServerOption $server = null
    ) {
    }

    public function resolve(string|array $command, array $options = []): array
    {
        return $this->parser
            ->normalize($command)
            ->pipe(fn($commands) => $this->replaceCommands($commands))
            ->pipe(fn($commands) => $this->prefixCommands($commands))
            ->pipe(fn($commands) => $commands->merge($this->buildOptions($options)))
            ->filter()
            ->values()
            ->all();
    }

    private function replaceCommands(Collection $commands): Collection
    {
        if (!$this->server) {
            return $commands;
        }

        $replace = $this->server->replaces();

        return $commands->map(fn($command) => $replace[$command] ?? $command);
    }

    private function prefixCommands(Collection $commands): Collection
    {
        if (!$this->server) {
            return $commands;
        }

        $prefixable = $this->server->prefixes();
        $serverCommand = $this->server->command();

        return $commands->flatMap(
            callback: fn($command) => \in_array($command, $prefixable, true)
            ? [$serverCommand, $command]
            : [$command]
        );
    }
}
