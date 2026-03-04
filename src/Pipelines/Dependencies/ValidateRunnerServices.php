<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Dependencies;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Verifiers\HerdServiceValidator;

final readonly class ValidateRunnerServices
{
    public function __construct(
        private HerdServiceValidator $herdValidator = new HerdServiceValidator()
    ) {
    }

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $runner = $this->getRunnerFromCommand($command);

        match ($runner) {
            ExternalCommandRunner::HERD => $this->herdValidator->validate($command),
            default => null,
        };

        return $next($command);
    }

    private function getRunnerFromCommand(InterruptibleCommand $command): ExternalCommandRunner
    {
        $runnerValue = $command->argument('runner');

        return ExternalCommandRunner::from($runnerValue);
    }
}
