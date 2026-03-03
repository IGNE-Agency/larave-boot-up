<?php

namespace Igne\LaravelBootstrap\Console;

use Igne\LaravelBootstrap\Contracts\Interruptible;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Illuminate\Console\Command;

abstract class InterruptibleCommand extends Command implements Interruptible
{
    protected const SIGINT = 2;

    protected const SIGTERM = 15;

    protected const DEFAULT_SIGNALS = [self::SIGINT, self::SIGTERM];

    protected array $interruptSignals;

    public ExternalCommandManager $externalProcessManager;

    public function __construct(array $interruptSignals = self::DEFAULT_SIGNALS)
    {
        parent::__construct();
        $this->interruptSignals = $interruptSignals ?? [];
    }

    final public function handle(): int
    {
        $runner = null;
        if ($this->hasArgument('runner')) {
            $runnerName = $this->argument('runner');
            if ($runnerName && \is_string($runnerName)) {
                $runner = $runnerName instanceof ExternalCommandRunner ? $runnerName : ExternalCommandRunner::from($runnerName);
            }
        }
        $this->externalProcessManager = new ExternalCommandManager(
            $runner,
            $this->output
        );

        $this->trap($this->interruptSignals, function (int $signal) {
            $this->shouldKeepRunning = false;

            $this->handleInterrupt($signal);
        });

        return $this->handleWithInterrupts();
    }

    abstract public function handleWithInterrupts(): int;

    abstract public function cleanup(int $signal): void;

    protected function handleInterrupt(int $signal): void
    {
        $this->warn('Interrupt signal received. Cleaning up...');
        $this->cleanup($signal);
        $this->externalProcessManager->stopAllProcesses();
        $this->info('Exit completed gracefully.');
        exit(Command::SUCCESS);
    }
}
