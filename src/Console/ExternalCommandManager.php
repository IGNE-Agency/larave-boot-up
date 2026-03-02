<?php

namespace Igne\LaravelBootstrap\Console;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;

class ExternalCommandManager
{
    private ?ExternalCommandRunner $withRunner;

    private ?OutputInterface $output;

    private bool $isSilent;

    protected array $processes = [];

    private ?PackageManager $packageManager = null;

    public function __construct(
        ?ExternalCommandRunner $withRunner = null,
        ?OutputInterface $output = null,
        bool $isSilent = false
    ) {
        $this->withRunner = $withRunner;
        $this->output = $output ?: new StreamOutput(STDOUT);
        $this->isSilent = $isSilent;
        $this->packageManager = $this->resolvePackageManager();
    }

    public function create(?bool $silent = null): ExternalCommand
    {
        return new ExternalCommand($this->withRunner, $this->output, $silent ?? $this->isSilent);
    }

    public function stopAllProcesses(): void
    {
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
    }

    public function isCommandAvailable(string $command): bool
    {
        return $this->callSilent([...Arr::wrap($command), '-v'])->isSuccessful();
    }

    public function isCommandRunning(string $command): bool
    {
        $result = $this->callSilent($command);

        return $result->isSuccessful() && Str::of($result->getOutput())->trim()->isNotEmpty();
    }

    public function lastProcess(): ?Process
    {
        $lastProcess = end($this->processes);

        return $lastProcess ?: null;
    }

    public function call(string|array $command, array $options = [], ?bool $silent = null, ?int $timeout = null): Process
    {
        $instance = $this->create($silent);
        if ($timeout) {
            $instance->timeout($timeout);
        }
        $instance->call($command, $options);
        $process = $instance->process();
        if ($process && $process->isRunning()) {
            $this->processes[] = $process;
        }
        $this->cleanupFinishedProcesses();

        return $process;
    }

    public function callSilent(string|array $command, array $options = []): Process
    {
        return $this->call($command, $options, true);
    }

    public function php(string|array $arguments, array $options = []): Process
    {
        return $this->call(['php', ...Arr::wrap($arguments)], $options);
    }

    public function composer(string|array $arguments, array $options = []): Process
    {
        return $this->call(['composer', ...Arr::wrap($arguments)], $options);
    }

    public function packageManager(string|array $arguments, array $options = []): Process
    {
        $pm = $this->packageManager ?? PackageManager::BUN;

        return $this->call([$pm->value, ...Arr::wrap($arguments)], $options);
    }

    public function getPackageManager(): PackageManager
    {
        return $this->packageManager ?? PackageManager::BUN;
    }

    public function waitFor(
        Closure $check,
        Closure $onSuccess,
        Closure $onFailure,
        ?Closure $onProgress = null,
        int $timeoutSeconds = 60,
        int $intervalMs = 1000,
        ?Closure $onInterrupt = null,
        ?Closure $isInterrupted = null
    ): void {
        $start = microtime(true);

        $onProgress ??= fn() => null;
        $onInterrupt ??= fn() => null;

        while ((microtime(true) - $start) < $timeoutSeconds) {
            if ($isInterrupted && $isInterrupted()) {
                $onInterrupt();

                return;
            }

            if ($check()) {
                $onSuccess();

                return;
            }

            $onProgress();
            usleep($intervalMs * 1000);
        }

        $onFailure($timeoutSeconds);
    }

    protected function cleanupFinishedProcesses(): void
    {
        $this->processes = array_filter(
            $this->processes,
            fn($process) => $process->isRunning()
        );
    }

    protected function resolvePackageManager(): PackageManager
    {
        $default = config('bootstrap.package_manager.default', 'bun');

        return PackageManager::from($default);
    }
}
