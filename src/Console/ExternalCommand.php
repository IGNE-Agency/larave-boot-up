<?php

namespace Igne\LaravelBootstrap\Console;

use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Resolvers\CommandResolver;
use Igne\LaravelBootstrap\Development\ServerProcessor;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;

final class ExternalCommand
{
    private Process $process;
    private array $command = [];
    private CommandResolver $resolver;
    private ServerProcessor $serverProcessor;
    private OutputInterface $output;
    private ?int $timeout = null;
    private bool $isSilent;

    public function __construct(
        ?DevServerOption $server = null,
        ?OutputInterface $output = null,
        bool $isSilent = false
    ) {
        $this->output = $output ?: new StreamOutput(STDOUT);
        $this->isSilent = $isSilent;
        $this->timeout = null;
        $this->resolver = new CommandResolver(
            new \Igne\LaravelBootstrap\Parsers\CommandParser(),
            $server
        );
        $this->serverProcessor = new ServerProcessor($this->output, $this->isSilent, $this->timeout);
    }

    public function withServer(?DevServerOption $server = null): self
    {
        $this->resolver = new CommandResolver(
            new \Igne\LaravelBootstrap\Parsers\CommandParser(),
            $server
        );

        return $this;
    }

    public function silent(bool $isSilent = true): self
    {
        $this->isSilent = $isSilent;
        $this->serverProcessor = new ServerProcessor($this->output, $this->isSilent, $this->timeout);

        return $this;
    }

    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;
        $this->serverProcessor = new ServerProcessor($this->output, $this->isSilent, $this->timeout);

        return $this;
    }

    public function call(string|array $command, array $options = []): int
    {
        $this->prepare($command, $options);

        return $this->run();
    }

    public function run(): int
    {
        $this->process = $this->serverProcessor->execute($this->command, base_path());

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

        return $this->serverProcessor->getOutput($this->process);
    }

    public function isSuccessful(): bool
    {
        return isset($this->process) && $this->serverProcessor->isSuccessful($this->process);
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
