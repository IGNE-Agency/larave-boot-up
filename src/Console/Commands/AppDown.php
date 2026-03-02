<?php

namespace Igne\LaravelBootstrap\Console\Commands;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Runners\ServeHerdRunner;
use Igne\LaravelBootstrap\Runners\ServeSailRunner;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

final class AppDown extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'app:down';

    protected $description = 'Shut down the local Laravel environment (Sail, Herd, etc.) and optionally clean up';

    public function handleWithInterrupts(): int
    {
        $this->info('Stopping application environment...');
        $this->newLine(1);

        $herdRunner = new ServeHerdRunner($this);
        $sailRunner = new ServeSailRunner($this);

        if ($herdRunner->isRunning()) {
            $shouldStopRunner = $this->shouldStopRunner('Herd');
            
            if ($shouldStopRunner) {
                $this->info('Stopping Laravel Herd...');
                $herdRunner->cleanup();
            } else {
                $this->info('Stopping processes but keeping Herd running...');
                $this->command->stopAllProcesses();
            }
        }
        
        if ($sailRunner->isRunning()) {
            $shouldStopRunner = $this->shouldStopRunner('Sail');
            
            if ($shouldStopRunner) {
                $this->info('Stopping Sail containers...');
                $sailRunner->cleanup();
            } else {
                $this->info('Stopping processes but keeping Sail running...');
                $this->command->stopAllProcesses();
            }
        }

        $this->info('Application environment has been stopped.');

        return Command::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up application environment...');
        $this->command->stopAllProcesses();
        $this->info('Exit completed gracefully.');
    }

    protected function shouldStopRunner(string $runnerName): bool
    {
        $shouldPrompt = config('bootstrap.shutdown.prompt_runner_stop', true);
        $defaultStop = config('bootstrap.shutdown.default_stop_runner', false);

        if (! $shouldPrompt) {
            return $defaultStop;
        }

        return $this->confirm(
            "Do you want to stop {$runnerName} itself? (No will only stop running processes)",
            $defaultStop
        );
    }
}
