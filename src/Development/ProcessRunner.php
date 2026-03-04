<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Development;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class ProcessRunner
{
    public function __construct(
        private readonly ?OutputInterface $output = null,
        private readonly bool $isSilent = false,
        private readonly ?int $timeout = null
    ) {
    }

    public function execute(array $command, string $workingDirectory): Process
    {
        $process = new Process($command, $workingDirectory, null, null, $this->timeout);

        $process->run(function ($type, $buffer) {
            if (!$this->isSilent && $this->output) {
                $this->output->write($buffer);
            }
        });

        if (!$this->isSilent && !$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    public function isSuccessful(Process $process): bool
    {
        return $process->isSuccessful();
    }

    public function getOutput(Process $process): ?string
    {
        return $process->isSuccessful()
            ? $process->getOutput()
            : $process->getErrorOutput();
    }
}
