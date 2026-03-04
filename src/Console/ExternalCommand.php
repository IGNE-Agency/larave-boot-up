<?php

namespace Igne\LaravelBootstrap\Console;

use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Resolvers\CommandResolver;
use Igne\LaravelBootstrap\Development\ProcessRunner;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;

final class ExternalCommand
{
    private Process $process;
    private array $command = [];
    private CommandResolver $resolver;
    private ProcessRunner $runner;
    private OutputInterface $output;
    private ?int $timeout = null;
    private bool $isSilent;

    public function __construct(
        ?ExternalCommandRunner $withRunner = null,
        ?OutputInterface $output = null,
        bool $isSilent = false
    ) {
        $this->output = $output ?: new StreamOutput(STDOUT);
        $this->isSilent = $isSilent;
        $this->timeout = null;
        $this->resolver = new CommandResolver(
            new \Igne\LaravelBootstrap\Parsers\CommandParser(),
            $withRunner
        );
        $this->runner = new ProcessRunner($this->output, $this->isSilent, $this->timeout);
    }

    public function withRunner(?ExternalCommandRunner $withRunner = null): self
    {
        $this->resolver = new CommandResolver(
            new \Igne\LaravelBootstrap\Parsers\CommandParser(),
            $withRunner
        );

        return $this;
    }

    public function silent(bool $isSilent = true): self
    {
        $this->isSilent = $isSilent;
        $this->runner = new ProcessRunner($this->output, $this->isSilent, $this->timeout);

        return $this;
    }

    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;
        $this->runner = new ProcessRunner($this->output, $this->isSilent, $this->timeout);

        return $this;
    }

    public function call(string|array $command, array $options = []): int
    {
        $this->prepare($command, $options);

        return $this->run();
    }

    public function run(): int
    {
        $this->process = $this->runner->execute($this->command, base_path());

        return $this->process->getExitCode();
    }

    public function process(): ?Process
    {
        return $this->process;
    }

    public function output(): ?string
    {
        if (!isset($this->process)) {
            return null;
        }

        return $this->runner->getOutput($this->process);
    }

    public function isSuccessful(): bool
    {
        return isset($this->process) && $this->runner->isSuccessful($this->process);
    }

    public function resolveCommand(string|array $command, array $options = []): array
    {
        return $this->resolver->resolve($command, $options);
    }

    protected function prepare(string|array $command, array $options = []): static
    {
        $this->command = $this->resolveCommand($command, $options);

        return $this;
    }

}
