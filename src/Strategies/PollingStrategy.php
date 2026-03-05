<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Strategies;

use Closure;

final class PollingStrategy
{
    public function waitFor(
        Closure $check,
        Closure $onSuccess,
        Closure $onFailure,
        ?Closure $onProgress = null,
        int $timeoutSeconds = 60,
        int $intervalMs = 1000,
        ?Closure $onInterrupt = null,
        ?Closure $isInterrupted = null
    ): void {
        $start = microtime(true);
        $onProgress ??= fn () => null;
        $onInterrupt ??= fn () => null;

        while ($this->shouldContinueWaiting($start, $timeoutSeconds)) {
            if ($this->isInterrupted($isInterrupted)) {
                $onInterrupt();

                return;
            }

            if ($check()) {
                $onSuccess();

                return;
            }

            $onProgress();
            $this->wait($intervalMs);
        }

        $onFailure($timeoutSeconds);
    }

    private function shouldContinueWaiting(float $start, int $timeoutSeconds): bool
    {
        return (microtime(true) - $start) < $timeoutSeconds;
    }

    private function isInterrupted(?Closure $isInterrupted): bool
    {
        return $isInterrupted && $isInterrupted();
    }

    private function wait(int $intervalMs): void
    {
        usleep($intervalMs * 1000);
    }
}
