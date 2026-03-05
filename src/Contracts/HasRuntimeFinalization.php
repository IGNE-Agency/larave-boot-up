<?php

namespace Igne\LaravelBootstrap\Contracts;

interface HasRuntimeFinalization
{
    public function finalizeRuntime(): self;
}
