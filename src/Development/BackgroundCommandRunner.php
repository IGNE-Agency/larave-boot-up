<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Development;

final class BackgroundCommandRunner
{
    public function executeInBackground(string $command): ?int
    {
        $fullCommand = $this->buildBackgroundCommand($command);
        $output = shell_exec($fullCommand);

        return $this->extractPid($output);
    }

    private function buildBackgroundCommand(string $command): string
    {
        return "{$command} > /dev/null 2>&1 & echo $!";
    }

    private function extractPid(?string $output): ?int
    {
        if ($output === null) {
            return null;
        }

        $pid = (int) trim($output);

        return $pid > 0 ? $pid : null;
    }
}
