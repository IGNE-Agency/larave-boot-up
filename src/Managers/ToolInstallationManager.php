<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Managers;

use Igne\LaravelBootstrap\Console\ExternalCommandManager;
use Igne\LaravelBootstrap\Development\ToolInstaller;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Resolvers\ConfigResolver;
use Igne\LaravelBootstrap\Traits\HasOutputMethods;
use Illuminate\Console\OutputStyle;

final class ToolInstallationManager
{
    use HasOutputMethods;

    public function __construct(
        private readonly ExternalCommandManager $commandManager,
        private readonly ConfigResolver $configResolver,
        private readonly ?OutputStyle $output = null
    ) {
    }

    protected function getOutputHandler(): mixed
    {
        return $this->output;
    }

    public function ensureInstalled(string $tool, ExternalCommandRunner $runner): void
    {
        if ($this->isInstalled($tool)) {
            return;
        }

        if (!$this->configResolver->isAutoInstallEnabled()) {
            throw new \RuntimeException("{$tool} is not installed. Please install it manually or enable auto_install in config.");
        }

        $this->install($tool, $runner);
    }

    private function isInstalled(string $tool): bool
    {
        return $this->commandManager->isCommandAvailable($tool);
    }

    private function install(string $tool, ExternalCommandRunner $runner): void
    {
        $this->displayInstallMessage($tool, $runner);

        $installer = new ToolInstaller();
        $installer->setRunner($runner);
        $installer->install($tool, 'latest', $this->output);

        $this->displaySuccessMessage($tool);
    }

    private function displayInstallMessage(string $tool, ExternalCommandRunner $runner): void
    {
        $this->comment("{$tool} not found. Installing (required for {$runner->value} runner)...");
    }

    private function displaySuccessMessage(string $tool): void
    {
        $this->info("{$tool} installed successfully. Note: You may need to restart your terminal or system for {$tool} to be fully functional.");
    }
}
