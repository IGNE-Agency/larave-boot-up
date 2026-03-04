<?php

namespace Igne\LaravelBootstrap\Console;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\PackageManager;
use Igne\LaravelBootstrap\Verifiers\CommandPresenceVerifier;
use Igne\LaravelBootstrap\Repositories\ProcessRepository;
use Igne\LaravelBootstrap\Strategies\PollingStrategy;
use Closure;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;

class ExternalCommandManager
{
    private ?ExternalCommandRunner $withRunner;
    private ?OutputInterface $output;
    private bool $isSilent;
    private ProcessRepository $processRepository;
    private CommandPresenceVerifier $presenceVerifier;
    private PollingStrategy $pollingStrategy;
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
        $this->processRepository = new ProcessRepository();
        $this->presenceVerifier = new CommandPresenceVerifier();
        $this->pollingStrategy = new PollingStrategy();
    }

    public function create(?bool $silent = null): ExternalCommand
    {
        return new ExternalCommand($this->withRunner, $this->output, $silent ?? $this->isSilent);
    }

    public function stopAllProcesses(): void
    {
        $this->processRepository->stopAll();
    }

    public function isCommandAvailable(string $command): bool
    {
        return $this->presenceVerifier->isAvailable($command);
    }

    public function isCommandRunning(string $command): bool
    {
        return $this->presenceVerifier->isRunning($command);
    }

    public function lastProcess(): ?Process
    {
        return $this->processRepository->getLastProcess();
    }

    public function call(string|array $command, array $options = [], ?bool $silent = null, ?int $timeout = null): Process
    {
        $instance = $this->create($silent);
        if ($timeout) {
            $instance->timeout($timeout);
        }
        $instance->call($command, $options);
        $process = $instance->process();
        $this->processRepository->register($process);
        $this->processRepository->cleanup();

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
        $this->pollingStrategy->waitFor(
            $check,
            $onSuccess,
            $onFailure,
            $onProgress,
            $timeoutSeconds,
            $intervalMs,
            $onInterrupt,
            $isInterrupted
        );
    }

    protected function resolvePackageManager(): PackageManager
    {
        $default = config('bootstrap.package_manager.default', 'bun');

        return PackageManager::from($default);
    }
}
