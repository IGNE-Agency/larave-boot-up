<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Resolvers;

use function Laravel\Prompts\select;

final class RunnerResolver
{
    public function determineRunner(?string $runnerArgument): string
    {
        if ($runnerArgument !== null) {
            return strtolower($runnerArgument);
        }

        $defaultRunner = $this->getDefaultRunner();

        if ($defaultRunner !== null && !$this->shouldPrompt()) {
            return strtolower($defaultRunner);
        }

        return $this->promptForRunner();
    }

    private function getDefaultRunner(): ?string
    {
        return config('bootstrap.runner.default');
    }

    private function shouldPrompt(): bool
    {
        return config('bootstrap.runner.prompt', true);
    }

    private function promptForRunner(): string
    {
        return select(
            label: 'Select your development environment',
            options: [
                'herd' => 'Laravel Herd - Fast local development with PHP and Nginx',
                'sail' => 'Laravel Sail - Docker-based development environment',
                'laravel' => 'Laravel Artisan - Built-in PHP development server',
            ],
            default: 'herd'
        );
    }
}
