<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Enums;

enum IDEOption: string
{
    case VSCODE = 'vscode';
    case CURSOR = 'cursor';
    case WINDSURF = 'windsurf';
    case PHPSTORM = 'phpstorm';

    public function detect(): bool
    {
        return match ($this) {
            self::VSCODE => $this->detectVSCode(),
            self::CURSOR => $this->detectCursor(),
            self::WINDSURF => $this->detectWindsurf(),
            self::PHPSTORM => $this->detectPhpStorm(),
        };
    }

    public function getCliCommand(): ?string
    {
        return match ($this) {
            self::VSCODE => 'code',
            self::CURSOR => 'cursor',
            self::WINDSURF => 'windsurf',
            self::PHPSTORM => null,
        };
    }

    public function isCliAvailable(): bool
    {
        $cliCommand = $this->getCliCommand();

        if (! $cliCommand) {
            return false;
        }

        $result = shell_exec("which {$cliCommand} 2>/dev/null");

        return ! empty(trim((string) $result));
    }

    public function getTerminalCommand(string $command): ?string
    {
        if (! $this->isCliAvailable()) {
            return null;
        }

        $basePath = base_path();

        return match ($this) {
            self::VSCODE => $this->buildVSCodeTerminalCommand($basePath, $command),
            self::CURSOR => $this->buildCursorTerminalCommand($basePath, $command),
            self::WINDSURF => $this->buildWindsurfTerminalCommand($basePath, $command),
            self::PHPSTORM => null,
        };
    }

    private function detectVSCode(): bool
    {
        return getenv('TERM_PROGRAM') === 'vscode' || getenv('VSCODE_INJECTION') === '1';
    }

    private function detectCursor(): bool
    {
        return getenv('TERM_PROGRAM') === 'cursor';
    }

    private function detectWindsurf(): bool
    {
        return getenv('TERM_PROGRAM') === 'windsurf' ||
               getenv('WINDSURF_CLI') === '1' ||
               stripos((string) getenv('TERM_PROGRAM_VERSION'), 'windsurf') !== false;
    }

    private function detectPhpStorm(): bool
    {
        return getenv('TERMINAL_EMULATOR') === 'JetBrains-JediTerm' ||
               getenv('IDEA_INITIAL_DIRECTORY') !== false;
    }

    private function buildVSCodeTerminalCommand(string $basePath, string $command): string
    {
        $escapedCommand = addslashes($command);

        return sprintf(
            'code --reuse-window --command "workbench.action.terminal.new" && sleep 0.5 && osascript -e \'tell application "System Events" to keystroke "cd %s && %s" & return\'',
            $basePath,
            $escapedCommand
        );
    }

    private function buildCursorTerminalCommand(string $basePath, string $command): string
    {
        $escapedCommand = addslashes($command);

        return sprintf(
            'cursor --reuse-window --command "workbench.action.terminal.new" && sleep 0.5 && osascript -e \'tell application "System Events" to keystroke "cd %s && %s" & return\'',
            $basePath,
            $escapedCommand
        );
    }

    private function buildWindsurfTerminalCommand(string $basePath, string $command): string
    {
        $escapedCommand = addslashes($command);

        return sprintf(
            'windsurf --reuse-window --command "workbench.action.terminal.new" && sleep 0.5 && osascript -e \'tell application "System Events" to keystroke "cd %s && %s" & return\'',
            $basePath,
            $escapedCommand
        );
    }

    public static function detectCurrent(): ?self
    {
        foreach (self::cases() as $ide) {
            if ($ide->detect()) {
                return $ide;
            }
        }

        return null;
    }
}
