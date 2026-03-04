<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Confirmations;

use function Laravel\Prompts\confirm;

final class ShutdownConfirmation
{
    public function shouldStopRunner(string $runnerName): bool
    {
        if (!$this->shouldPrompt()) {
            return $this->getDefaultBehavior();
        }

        return $this->promptUser($runnerName);
    }

    private function shouldPrompt(): bool
    {
        return config('bootstrap.shutdown.prompt_runner_stop', true);
    }

    private function getDefaultBehavior(): bool
    {
        return config('bootstrap.shutdown.default_stop_runner', false);
    }

    private function promptUser(string $runnerName): bool
    {
        return confirm(
            label: "Do you want to stop {$runnerName} itself?",
            default: $this->getDefaultBehavior(),
            yes: 'Stop runner',
            no: 'Keep runner (only stop processes)',
            hint: 'Choose whether to fully stop the runner or just the processes'
        );
    }
}
