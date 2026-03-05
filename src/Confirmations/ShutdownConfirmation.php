<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Confirmations;

use function Laravel\Prompts\confirm;

final class ShutdownConfirmation
{
    public function shouldStopServer(string $serverName): bool
    {
        if (! $this->shouldPrompt()) {
            return $this->getDefaultBehavior();
        }

        return $this->promptUser($serverName);
    }

    private function shouldPrompt(): bool
    {
        return config('bootstrap.shutdown.prompt_server_stop', true);
    }

    private function getDefaultBehavior(): bool
    {
        return config('bootstrap.shutdown.default_stop_server', false);
    }

    private function promptUser(string $serverName): bool
    {
        return confirm(
            label: "Do you want to stop {$serverName} itself?",
            default: $this->getDefaultBehavior(),
            yes: 'Stop server',
            no: 'Keep server (only stop processes)',
            hint: 'Choose whether to fully stop the server or just the processes'
        );
    }
}
