<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Contracts\ProvidesBootstrapCommands;
use Igne\LaravelBootstrap\Contracts\ProvidesCustomCommands;
use Igne\LaravelBootstrap\Data\DTOs\BootstrapCommand;
use Igne\LaravelBootstrap\Enums\CommandEnvironment;
use Igne\LaravelBootstrap\Traits\BuildsCommandOptions;

abstract readonly class RunCustomCommands implements ProvidesCustomCommands
{
    use BuildsCommandOptions;

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $provider = $this->getCommandProvider();

        if ($provider === null) {
            return $next($command);
        }

        $commands = $this->getCommands($provider);

        if (empty($commands)) {
            return $next($command);
        }

        $command->info($this->getInfoMessage());

        foreach ($commands as $bootstrapCommand) {
            $this->executeCommand($command, $bootstrapCommand);
        }

        return $next($command);
    }

    abstract public function getCommands(ProvidesBootstrapCommands $provider): array;

    abstract public function getInfoMessage(): string;

    private function getCommandProvider(): ?ProvidesBootstrapCommands
    {
        if (!app()->bound(ProvidesBootstrapCommands::class)) {
            return null;
        }

        return app(ProvidesBootstrapCommands::class);
    }

    private function executeCommand(InterruptibleCommand $command, BootstrapCommand $bootstrapCommand): void
    {
        if ($bootstrapCommand->message !== null) {
            $command->info($bootstrapCommand->message);
        }

        match ($bootstrapCommand->environment) {
            CommandEnvironment::ARTISAN => $this->runArtisan($command, $bootstrapCommand),
            CommandEnvironment::COMPOSER => $this->runComposer($command, $bootstrapCommand),
            CommandEnvironment::PACKAGE_MANAGER => $this->runPackageManager($command, $bootstrapCommand),
        };
    }

    private function runArtisan(InterruptibleCommand $command, BootstrapCommand $bootstrapCommand): void
    {
        $command->call($bootstrapCommand->command, $bootstrapCommand->args);
    }

    private function runComposer(InterruptibleCommand $command, BootstrapCommand $bootstrapCommand): void
    {
        $command->externalProcessManager->composer(
            $bootstrapCommand->command,
            $this->buildOptions($bootstrapCommand->args)
        );
    }

    private function runPackageManager(InterruptibleCommand $command, BootstrapCommand $bootstrapCommand): void
    {
        $command->externalProcessManager->packageManager(
            $bootstrapCommand->getFullCommand()
        );
    }
}
