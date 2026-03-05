<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Managers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Traits\HasOutputMethods;
use Illuminate\Console\OutputStyle;

final class ComposerDependencyManager
{
    use HasOutputMethods;

    public function __construct(
        private readonly ExternalCommandManager $commandManager,
        private readonly ?OutputStyle $output = null
    ) {}

    protected function getOutputHandler(): mixed
    {
        return $this->output;
    }

    public function install(): void
    {
        $this->info('Installing dependencies...');
        $this->commandManager->composer('install', $this->getInstallFlags());
    }

    public function update(): void
    {
        $this->info('Updating dependencies...');
        $this->commandManager->composer('update', $this->getInstallFlags());
        $this->commandManager->composer('run upgrade');
    }

    public function regenerateLockFile(): void
    {
        $this->displayRegenerationMessages();
        $this->commandManager->composer('update', $this->getLockUpdateFlags());
        $this->info('Lock file regenerated. Installing dependencies...');
    }

    private function getInstallFlags(): array
    {
        return ['--no-interaction', '--prefer-dist'];
    }

    private function getLockUpdateFlags(): array
    {
        return ['--lock', '--no-interaction'];
    }

    private function displayRegenerationMessages(): void
    {
        $this->comment('Lock file is out of sync with composer.json.');
        $this->info('Regenerating lock file without updating package versions...');
    }
}
