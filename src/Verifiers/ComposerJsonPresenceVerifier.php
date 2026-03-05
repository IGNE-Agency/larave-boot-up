<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Verifiers;

use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Igne\LaravelBootstrap\Traits\HasOutputMethods;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;

final class ComposerJsonPresenceVerifier
{
    use HasOutputMethods;

    public function __construct(
        private readonly ?OutputStyle $output = null
    ) {}

    protected function getOutputHandler(): mixed
    {
        return $this->output;
    }

    public function validate(): void
    {
        if ($this->exists()) {
            return;
        }

        $this->displayError();
        $this->throwException();
    }

    private function exists(): bool
    {
        return File::exists(base_path('composer.json'));
    }

    private function displayError(): void
    {
        $this->error('composer.json not found in project root.');
        $this->newLine();
        $this->line('This package requires a valid Laravel project with composer.json.');
        $this->line('Please ensure you are running this command from a Laravel project root.');
    }

    private function throwException(): void
    {
        throw new DependencyCheckException('composer.json file is required but not found in project root.');
    }
}
