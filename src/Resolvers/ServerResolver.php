<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Resolvers;

use function Laravel\Prompts\select;

final class ServerResolver
{
    public function determineServer(?string $serverArgument): string
    {
        if ($serverArgument !== null) {
            return strtolower($serverArgument);
        }

        $defaultServer = $this->getDefaultServer();

        if ($defaultServer !== null && ! $this->shouldPrompt()) {
            return strtolower($defaultServer);
        }

        return $this->promptForServer();
    }

    private function getDefaultServer(): ?string
    {
        return config('bootstrap.server.default');
    }

    private function shouldPrompt(): bool
    {
        return config('bootstrap.server.prompt', true);
    }

    private function promptForServer(): string
    {
        return select(
            label: 'Select your development server',
            options: [
                'herd' => 'Laravel Herd - Fast local development with PHP and Nginx',
                'sail' => 'Laravel Sail - Docker-based development server',
                'laravel' => 'Laravel Artisan - Built-in PHP development server',
            ],
            default: 'herd'
        );
    }
}
