<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Services;

final class IDEDetector
{
    public function isRunningInIDE(): bool
    {
        return $this->isVSCode() || $this->isPhpStorm() || $this->isCursor() || $this->isWindsurf();
    }

    public function getIDEType(): ?string
    {
        if ($this->isVSCode()) {
            return 'vscode';
        }

        if ($this->isPhpStorm()) {
            return 'phpstorm';
        }

        if ($this->isCursor()) {
            return 'cursor';
        }

        if ($this->isWindsurf()) {
            return 'windsurf';
        }

        return null;
    }

    public function getIDETerminalCommand(string $command): ?string
    {
        $basePath = base_path();
        $ideType = $this->getIDEType();

        return match ($ideType) {
            'vscode' => $this->getVSCodeTerminalCommand($basePath, $command),
            'cursor' => $this->getCursorTerminalCommand($basePath, $command),
            'windsurf' => $this->getWindsurfTerminalCommand($basePath, $command),
            'phpstorm' => null, // PhpStorm doesn't have CLI terminal opening
            default => null,
        };
    }

    private function isVSCode(): bool
    {
        return getenv('TERM_PROGRAM') === 'vscode' || getenv('VSCODE_INJECTION') === '1';
    }

    private function isPhpStorm(): bool
    {
        return getenv('TERMINAL_EMULATOR') === 'JetBrains-JediTerm' ||
            getenv('IDEA_INITIAL_DIRECTORY') !== false;
    }

    private function isCursor(): bool
    {
        return getenv('TERM_PROGRAM') === 'cursor';
    }

    private function isWindsurf(): bool
    {
        return getenv('TERM_PROGRAM') === 'windsurf' ||
            getenv('WINDSURF_CLI') === '1' ||
            stripos((string) getenv('TERM_PROGRAM_VERSION'), 'windsurf') !== false;
    }

    private function getVSCodeTerminalCommand(string $basePath, string $command): string
    {
        $escapedCommand = addslashes($command);

        return sprintf(
            'code --reuse-window --command "workbench.action.terminal.new" && sleep 0.5 && osascript -e \'tell application "System Events" to keystroke "cd %s && %s" & return\'',
            $basePath,
            $escapedCommand
        );
    }

    private function getCursorTerminalCommand(string $basePath, string $command): string
    {
        $escapedCommand = addslashes($command);

        return sprintf(
            'cursor --reuse-window --command "workbench.action.terminal.new" && sleep 0.5 && osascript -e \'tell application "System Events" to keystroke "cd %s && %s" & return\'',
            $basePath,
            $escapedCommand
        );
    }

    private function getWindsurfTerminalCommand(string $basePath, string $command): string
    {
        $escapedCommand = addslashes($command);

        return sprintf(
            'windsurf --reuse-window --command "workbench.action.terminal.new" && sleep 0.5 && osascript -e \'tell application "System Events" to keystroke "cd %s && %s" & return\'',
            $basePath,
            $escapedCommand
        );
    }
}
