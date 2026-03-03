<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Contracts;

interface ChecksVersions
{
    public function getLatestSafeVersion(string $tool): string;
}
