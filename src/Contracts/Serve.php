<?php

namespace Igne\LaravelBootstrap\Contracts;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Illuminate\Console\OutputStyle;

interface Serve
{
    public function serve(): int;

    public function postServe(): int;

    public function cleanup(): void;

    public function isAvailableOnSystem(): bool;

    public function isRunning(): bool;

    public function getUrl(): string;

    public function getRunner(): ExternalCommandRunner;

    public function getOutput(): ?OutputStyle;
}
