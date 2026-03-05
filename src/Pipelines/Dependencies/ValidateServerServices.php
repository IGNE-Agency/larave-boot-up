<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Dependencies;

use Closure;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Verifiers\HerdServiceValidator;

final readonly class ValidateServerServices
{
    public function __construct(
        private HerdServiceValidator $herdValidator = new HerdServiceValidator
    ) {}

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $server = $this->getServerFromCommand($command);

        match ($server) {
            DevServerOption::HERD => $this->herdValidator->validate($command),
            default => null,
        };

        return $next($command);
    }

    private function getServerFromCommand(InterruptibleCommand $command): DevServerOption
    {
        $serverValue = $command->argument('server');

        return DevServerOption::from($serverValue);
    }
}
