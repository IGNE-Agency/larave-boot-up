<?php

namespace Igne\LaravelBootstrap\Enums;

enum PackageManager: string
{
    case BUN = 'bun';
    case YARN = 'yarn';
    case NPM = 'npm';

    public function installCommand(): string
    {
        return match ($this) {
            self::BUN => 'install',
            self::YARN => 'install',
            self::NPM => 'install',
        };
    }

    public function updateCommand(): string
    {
        return match ($this) {
            self::BUN => 'update',
            self::YARN => 'upgrade',
            self::NPM => 'update',
        };
    }

    public function buildCommand(): string
    {
        return match ($this) {
            self::BUN => 'run build',
            self::YARN => 'build',
            self::NPM => 'run build',
        };
    }

    public function devCommand(): string
    {
        return match ($this) {
            self::BUN => 'run dev',
            self::YARN => 'dev',
            self::NPM => 'run dev',
        };
    }

    public function installPackageManagerCommand(string $version = 'latest'): string
    {
        return match ($this) {
            self::BUN => OSCommand::INSTALL_BUN->forVersion($version)->execute(),
            self::YARN => OSCommand::INSTALL_YARN->forVersion($version)->execute(),
            self::NPM => OSCommand::INSTALL_NPM->forVersion($version)->execute(),
        };
    }
}
