<?php

namespace Igne\LaravelBootstrap;

use Igne\LaravelBootstrap\Contracts\Serve;
use Illuminate\Support\Facades\Artisan;

final class ServeApplication
{
    public function __construct(protected Serve $runner) {}

    public function boot(): void
    {
        $this->runner->serve();
        Artisan::call('check:dependencies', [
            'runner' => $this->runner->getRunner(),
        ], $this->runner->getOutput());
        Artisan::call('check:database', [
            'runner' => $this->runner->getRunner(),
        ], $this->runner->getOutput());
        Artisan::call('app:deploy', [
            'runner' => $this->runner->getRunner(),
        ], $this->runner->getOutput());
        $this->runner->postServe();
    }
}
