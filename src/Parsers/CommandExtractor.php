<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Parsers;

final class CommandExtractor
{
    public function extractCommand(string $fullCommand): string
    {
        if (preg_match('/cd .+ && (.+)/', $fullCommand, $matches)) {
            return $matches[1];
        }

        return $fullCommand;
    }
}
