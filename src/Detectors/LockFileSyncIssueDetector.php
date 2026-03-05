<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Detectors;

use Illuminate\Support\Collection;

final class LockFileSyncIssueDetector
{
    public function isSyncIssue(string $errorMessage): bool
    {
        return $this->getSyncPatterns()
            ->contains(fn (string $pattern): bool => $this->messageContainsPattern($errorMessage, $pattern));
    }

    private function getSyncPatterns(): Collection
    {
        return collect([
            'lock file is not up to date',
            'hash does not match',
            'content-hash',
            'lock file out of date',
            'run `composer update`',
        ]);
    }

    private function messageContainsPattern(string $message, string $pattern): bool
    {
        return stripos($message, $pattern) !== false;
    }
}
