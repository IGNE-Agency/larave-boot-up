<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Data\DTOs;

use Igne\LaravelBootstrap\Enums\CommandEnvironmentOption;
use Igne\LaravelBootstrap\Traits\BuildsCommandOptions;
use InvalidArgumentException;

final readonly class BootstrapCommand
{
    use BuildsCommandOptions;
    public function __construct(
        public CommandEnvironmentOption $environment,
        public string $command,
        public ?string $message,
        public array $args = [],
    ) {
        $this->validate();
    }

    public static function artisan(string $command, ?string $message = null, array $args = []): self
    {
        return new self(CommandEnvironmentOption::ARTISAN, $command, $message, $args);
    }

    public static function composer(string $command, ?string $message = null, array $args = []): self
    {
        return new self(CommandEnvironmentOption::COMPOSER, $command, $message, $args);
    }

    public static function packageManager(string $command, ?string $message = null, array $args = []): self
    {
        return new self(CommandEnvironmentOption::PACKAGE_MANAGER, $command, $message, $args);
    }

    private function validate(): void
    {
        if (empty(trim($this->command))) {
            throw new InvalidArgumentException('Command cannot be empty.');
        }

        $this->validateCommandSafety();
        $this->validateArguments();
    }

    private function validateCommandSafety(): void
    {
        $dangerousPatterns = [
            'rm ',
            'del ',
            'format',
            'shutdown',
            'reboot',
            'kill',
            'pkill',
            'dd ',
            'mkfs',
            '> /dev/',
            'curl.*|.*sh',
            'wget.*|.*sh',
            'eval',
            'exec',
            'system(',
            'shell_exec',
            'passthru',
            '`',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match('/' . preg_quote($pattern, '/') . '/i', $this->command)) {
                throw new InvalidArgumentException(
                    "Command '{$this->command}' contains potentially dangerous pattern: {$pattern}"
                );
            }
        }

        if (str_contains($this->command, '&&') || str_contains($this->command, '||') || str_contains($this->command, ';')) {
            throw new InvalidArgumentException(
                "Command '{$this->command}' cannot contain command chaining operators (&&, ||, ;)"
            );
        }
    }

    private function validateArguments(): void
    {
        foreach ($this->args as $key => $value) {
            if (!\is_string($key)) {
                throw new InvalidArgumentException('All argument keys must be strings.');
            }

            if (!\is_string($value) && !\is_numeric($value) && !\is_bool($value) && !\is_array($value)) {
                throw new InvalidArgumentException(
                    "Argument '{$key}' has invalid type. Only string, numeric, boolean, or array values are allowed."
                );
            }

            if (\is_array($value)) {
                foreach ($value as $item) {
                    if (!\is_string($item) && !\is_numeric($item)) {
                        throw new InvalidArgumentException(
                            "Array argument '{$key}' contains invalid items. Only strings and numbers are allowed."
                        );
                    }
                }
            }
        }
    }

    public function getFullCommand(): string
    {
        $options = $this->buildOptions($this->args);

        return trim($this->command . ' ' . implode(' ', $options));
    }
}
