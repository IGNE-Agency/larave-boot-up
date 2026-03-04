<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Traits;

trait HasOutputMethods
{
    /**
     * Get the output handler (OutputStyle or InterruptibleCommand).
     * Classes using this trait should implement this method.
     */
    abstract protected function getOutputHandler(): mixed;

    protected function info(string $message): void
    {
        $this->getOutputHandler()?->info($message);
    }

    protected function warn(string $message): void
    {
        $handler = $this->getOutputHandler();

        if (method_exists($handler, 'warning')) {
            $handler?->warning($message);
        } elseif (method_exists($handler, 'warn')) {
            $handler?->warn($message);
        }
    }

    protected function error(string $message): void
    {
        $this->getOutputHandler()?->error($message);
    }

    protected function line(string $message, ?string $style = null): void
    {
        $handler = $this->getOutputHandler();

        if (method_exists($handler, 'line')) {
            $handler?->line($message, $style);
        } elseif (method_exists($handler, 'writeln')) {
            $handler?->writeln($message);
        }
    }

    protected function comment(string $message): void
    {
        $handler = $this->getOutputHandler();

        if (method_exists($handler, 'writeln')) {
            $handler?->writeln("<comment>{$message}</comment>");
        } elseif (method_exists($handler, 'line')) {
            $handler?->line($message);
        }
    }

    protected function newLine(int $count = 1): void
    {
        $this->getOutputHandler()?->newLine($count);
    }

    protected function write(string $message): void
    {
        $this->getOutputHandler()?->write($message);
    }

    protected function displaySectionHeader(string $title): void
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info($title);
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
    }
}
