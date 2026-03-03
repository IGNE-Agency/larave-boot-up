<?php

declare(strict_types=1);

namespace App\Bootstrap;

use Igne\LaravelBootstrap\Contracts\ProvidesBootstrapCommands;
use Igne\LaravelBootstrap\Data\DTOs\BootstrapCommand;

/**
 * Example implementation of custom bootstrap commands.
 * 
 * Copy this file to your app/Bootstrap directory and register it in your AppServiceProvider:
 * 
 * $this->app->singleton(
 *     \Igne\LaravelBootstrap\Contracts\ProvidesBootstrapCommands::class,
 *     \App\Bootstrap\CustomBootstrapCommands::class
 * );
 */
final class CustomBootstrapCommands implements ProvidesBootstrapCommands
{
    /**
     * Commands to run after dependencies are installed but before migrations.
     * 
     * Use this for:
     * - Code generation that doesn't depend on database schema
     * - Type generation from source files
     * - Asset compilation preparation
     * 
     * @return array<BootstrapCommand>
     */
    public function beforeMigrations(): array
    {
        return [
            // Example: Generate TypeScript routes and actions
            BootstrapCommand::artisan(
                command: 'wayfinder:generate',
                message: 'Running Wayfinder. Generating Typescript Routes, Actions and Wayfinder...',
                args: [
                    '--path' => 'resources/js/wayfinder',
                ]
            ),

            // Example: Generate Zod schemas from resources
            // BootstrapCommand::packageManager(
            //     command: 'run zodgen',
            //     message: 'Running Zodgen. Generating Typescript zod types from resources...',
            //     args: []
            // ),
        ];
    }

    /**
     * Commands to run after migrations but before caching and queue workers.
     * 
     * Use this for:
     * - Model-based type generation (requires database schema)
     * - Cache warming
     * - Post-migration data processing
     * 
     * @return array<BootstrapCommand>
     */
    public function afterMigrations(): array
    {
        return [
            // Example: Generate TypeScript types from Eloquent models
            BootstrapCommand::artisan(
                command: 'model:typer',
                message: 'Running ModelTyper. Generating Typescript types from models...',
                args: []
            ),

            // Example: Optimize composer autoloader
            // BootstrapCommand::composer(
            //     command: 'dump-autoload',
            //     message: 'Optimizing composer autoloader...',
            //     args: ['--optimize' => true]
            // ),
        ];
    }
}
