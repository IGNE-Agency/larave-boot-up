<?php

namespace Igne\LaravelBootstrap\Contracts;

interface Interruptible
{
    public function handleWithInterrupts(): int;

    public function cleanup(int $signal): void;
}
