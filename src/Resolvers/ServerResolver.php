<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Resolvers;

use Igne\LaravelBootstrap\Enums\DevServerOption;
use Igne\LaravelBootstrap\Exceptions\ServeException;

use function Laravel\Prompts\select;

final class ServerResolver
{
    public function determineServer(?string $serverArgument): DevServerOption
    {
        $server = $serverArgument
            ?? $this->promptOrDefault()
            ?? throw new ServeException('No development server configured. Set bootstrap.server.default or provide server option.');

        return DevServerOption::from(strtolower($server));
    }

    private function promptOrDefault(): ?string
    {
        $default = config('bootstrap.server.default');

        if (config('bootstrap.server.prompt', true) && app()->runningInConsole()) {
            return select(
                label: 'Select your development server',
                options: [
                    'herd' => 'Laravel Herd - Fast local development with PHP and Nginx',
                    'sail' => 'Laravel Sail - Docker-based development server',
                    'laravel' => 'Laravel Artisan - Built-in PHP development server',
                ],
                default: $default
            );
        }

        return $default;
    }
}
