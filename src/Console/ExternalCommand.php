<?php

namespace Igne\LaravelBootstrap\Console;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Traits\BuildsCommandOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class ExternalCommand
{
    use BuildsCommandOptions;
    private Process $process;

    private array $command = [];

    private ?ExternalCommandRunner $withRunner;

    private ?OutputInterface $output;

    private ?int $timeout = null;

    private bool $isSilent;

    public function __construct(
        ?ExternalCommandRunner $withRunner = null,
        ?OutputInterface $output = null,
        bool $isSilent = false
    ) {
        $this->withRunner = $withRunner;
        $this->output = $output ?: new StreamOutput(STDOUT);
        $this->isSilent = $isSilent;
        $this->timeout = null;
    }

    public function withRunner(?ExternalCommandRunner $withRunner = null): self
    {
        $this->withRunner = $withRunner;

        return $this;
    }

    public function silent(bool $isSilent = true): self
    {
        $this->isSilent = $isSilent;

        return $this;
    }

    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function call(string|array $command, array $options = []): int
    {
        $this->prepare($command, $options);

        return $this->run();
    }

    public function run(): int
    {
        $this->process = new Process($this->command, base_path(), null, null, $this->timeout);
        $this->process->run(function ($type, $buffer) {
            if (!$this->isSilent) {
                $this->output->write($buffer);
            }
        });

        if (!$this->isSilent && !$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }

        return $this->process->getExitCode();
    }

    public function process(): ?Process
    {
        return $this->process;
    }

    public function output(): ?string
    {
        if (!$this->process) {
            return null;
        }

        return $this->process->isSuccessful()
            ? $this->process->getOutput()
            : $this->process->getErrorOutput();
    }

    public function isSuccessful(): bool
    {
        return $this->process?->isSuccessful() ?? false;
    }

    public function resolveCommand(string|array $command, array $options = []): array
    {
        return collect($command)
            ->pipe(fn($input) => $this->normalizeCommand($input))
            ->pipe(fn($commands) => $this->replaceCommands($commands))
            ->pipe(fn($commands) => $this->prefixCommands($commands))
            ->pipe(fn($commands) => $commands->merge($this->buildOptions($options)))
            ->filter()
            ->values()
            ->all();
    }

    protected function prepare(string|array $command, array $options = []): static
    {
        $this->command = $this->resolveCommand($command, $options);

        return $this;
    }

    protected function normalizeCommand($command): Collection
    {
        return collect(Arr::wrap($command))
            ->flatten()
            ->flatMap(
                fn($item) => \is_string($item)
                ? Str::of($item)->trim()->explode(' ')
                : Arr::wrap($item)
            )
            ->filter()
            ->values();
    }


    protected function replaceCommands(Collection $commands): Collection
    {
        if (!$this->withRunner) {
            return $commands;
        }

        $replace = $this->withRunner->replaces();

        return $commands->map(fn($command) => $replace[$command] ?? $command);
    }

    protected function prefixCommands(Collection $commands): Collection
    {
        if (!$this->withRunner) {
            return $commands;
        }

        $prefixable = $this->withRunner->prefixes();
        $runnerCommand = $this->withRunner->command();

        return $commands->flatMap(
            callback: fn($command) => \in_array($command, $prefixable, true)
            ? [$runnerCommand, $command]
            : [$command]
        );
    }
}
