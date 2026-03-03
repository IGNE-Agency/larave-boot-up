<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

interface InstallsTools
{
    public function install(string $tool, string $version, ?OutputInterface $output = null): void;

    public function update(string $tool, string $version, ?OutputInterface $output = null): void;
}
