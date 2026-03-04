<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Development;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class ShellCommandRunner
{
    public function run(string $command, ?OutputInterface $output = null): void
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300);

        $process->run(function ($type, $buffer) use ($output) {
            if ($output) {
                $output->write($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Failed to run command: {$command}");
        }
    }
}
