<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Strategies;

use Symfony\Component\Console\Output\OutputInterface;

interface InstallationStrategy
{
    public function install(string $version, ?OutputInterface $output): void;
}
