<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Bootstrap;

use Closure;
use Igne\LaravelBootstrap\Contracts\Serve;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

final readonly class DeployApplication
{
    public function handle(Serve $runner, Closure $next): Serve
    {
        $separateTerminal = config('bootstrap.deploy.separate_terminal', true);

        if ($separateTerminal && $this->canOpenTerminal()) {
            $this->deployInSeparateTerminal($runner);
        } else {
            Artisan::call('app:deploy', [
                'runner' => $runner->getRunner(),
            ], $runner->getOutput());
        }

        return $next($runner);
    }

    private function canOpenTerminal(): bool
    {
        return OSCommand::OPEN_TERMINAL->canExecute();
    }

    private function deployInSeparateTerminal(Serve $runner): void
    {
        $terminalCommand = OSCommand::OPEN_TERMINAL
            ->withCommand(new Command("app:deploy {$runner->getRunner()->value}"))
            ->execute();

        if ($terminalCommand) {
            shell_exec("{$terminalCommand} > /dev/null 2>&1 &");
        }
    }
}
