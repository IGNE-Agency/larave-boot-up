<?php

namespace Igne\LaravelBootstrap\Contracts;

use Igne\LaravelBootstrap\Enums\DevServerOption;
use Illuminate\Console\OutputStyle;

interface Server
{
    public function serve(): int;

    public function postServe(): int;

    public function cleanup(): void;

    public function isAvailableOnSystem(): bool;

    public function isRunning(): bool;

    public function getUrl(): string;

    public function getServer(): DevServerOption;

    public function getOutput(): ?OutputStyle;
}
