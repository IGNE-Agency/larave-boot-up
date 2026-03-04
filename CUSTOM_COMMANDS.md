# Custom Bootstrap Commands

This package provides a safe and flexible hook system that allows project maintainers to register custom commands that run during the bootstrap process.

## Overview

You can register custom commands to run at two points in the bootstrap lifecycle:
- **Before migrations**: After dependencies are installed but before database migrations
- **After migrations**: After database migrations but before caching and queue workers start

## Supported Server

Commands can run in three safe server:
- **Artisan**: Laravel artisan commands
- **Composer**: Composer commands
- **Package Manager**: npm/yarn/pnpm commands

## Setup

### 1. Create a Bootstrap Command Provider

Create a class that implements `ProvidesBootstrapCommands`:

```php
<?php

namespace App\Bootstrap;

use Igne\LaravelBootstrap\Contracts\ProvidesBootstrapCommands;
use Igne\LaravelBootstrap\Data\DTOs\BootstrapCommand;

final class CustomBootstrapCommands implements ProvidesBootstrapCommands
{
    public function beforeMigrations(): array
    {
        return [
            BootstrapCommand::artisan(
                command: 'wayfinder:generate',
                message: 'Generating Typescript Routes, Actions and Wayfinder...',
                args: [
                    '--path' => 'resources/js/wayfinder',
                ]
            ),
            
            BootstrapCommand::packageManager(
                command: 'run zodgen',
                message: 'Generating Zod types from resources...',
                args: []
            ),
        ];
    }

    public function afterMigrations(): array
    {
        return [
            BootstrapCommand::artisan(
                command: 'model:typer',
                message: 'Generating Typescript types from models...',
                args: []
            ),
            
            BootstrapCommand::composer(
                command: 'dump-autoload',
                message: 'Optimizing autoloader...',
                args: ['--optimize' => true]
            ),
        ];
    }
}
```

### 2. Register the Provider

In your `AppServiceProvider` or a dedicated service provider:

```php
<?php

namespace App\Providers;

use App\Bootstrap\CustomBootstrapCommands;
use Igne\LaravelBootstrap\Contracts\ProvidesBootstrapCommands;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProvidesBootstrapCommands::class,
            CustomBootstrapCommands::class
        );
    }
}
```

## Usage Examples

### Artisan Commands

```php
BootstrapCommand::artisan(
    command: 'cache:clear',
    message: 'Clearing application cache...',
    args: []
)

BootstrapCommand::artisan(
    command: 'migrate',
    message: 'Running migrations...',
    args: [
        '--force' => true,
        '--seed' => true,
    ]
)

BootstrapCommand::artisan(
    command: 'telescope:prune',
    message: 'Pruning old Telescope entries...',
    args: [
        '--hours' => 48,
    ]
)
```

### Composer Commands

```php
BootstrapCommand::composer(
    command: 'dump-autoload',
    message: 'Regenerating autoload files...',
    args: ['--optimize' => true]
)

BootstrapCommand::composer(
    command: 'run post-install-cmd',
    message: 'Running post-install scripts...',
    args: []
)
```

### Package Manager Commands

```php
BootstrapCommand::packageManager(
    command: 'run build',
    message: 'Building frontend assets...',
    args: []
)

BootstrapCommand::packageManager(
    command: 'run type-check',
    message: 'Running TypeScript type checking...',
    args: []
)
```

## Argument Types

The `args` array supports the following value types:

### String/Numeric Values
```php
args: [
    '--path' => 'resources/js',
    '--timeout' => 30,
]
```

### Boolean Flags
```php
args: [
    '--force' => true,    // Includes the flag
    '--no-cache' => false, // Excludes the flag
]
```

### Array Values (Multiple Values for Same Key)
```php
args: [
    '--exclude' => ['vendor', 'node_modules', 'storage'],
]
// Results in: --exclude=vendor --exclude=node_modules --exclude=storage
```

## Safety Features

The system includes multiple safety checks to prevent dangerous operations:

### Blocked Patterns
- File deletion commands (`rm`, `del`)
- System commands (`shutdown`, `reboot`, `kill`)
- Disk operations (`dd`, `mkfs`, `format`)
- Command chaining (`&&`, `||`, `;`)
- Code execution (`eval`, `exec`, shell pipes)
- Dangerous redirects (`> /dev/`)

### Validation
- Commands must be non-empty
- Messages must be non-empty
- Arguments must use allowed types only
- No command chaining or piping allowed
- Commands are restricted to Artisan, Composer, or Package Manager environments

## Bootstrap Lifecycle

The complete bootstrap process runs in this order:

1. **Install Composer Dependencies**
2. **Build Frontend Assets**
3. **🔹 Run Custom Commands (Before Migrations)**
4. **Run Database Migrations**
5. **🔹 Run Custom Commands (After Migrations)**
6. **Cache Framework Files**
7. **Start Queue Worker**

## Error Handling

If any custom command fails:
- The bootstrap process will stop
- An exception will be thrown with the error details
- No subsequent commands will run

Make sure your commands are idempotent and can handle being run multiple times.

## Best Practices

1. **Keep commands fast**: Bootstrap should be quick
2. **Make commands idempotent**: They may run multiple times
3. **Use appropriate timing**:
   - Before migrations: Code generation, type checking
   - After migrations: Model-based generation, cache warming
4. **Provide clear messages**: Help developers understand what's happening
5. **Handle failures gracefully**: Ensure commands won't break on re-runs

## Example: Complete Setup

```php
<?php

namespace App\Bootstrap;

use Igne\LaravelBootstrap\Contracts\ProvidesBootstrapCommands;
use Igne\LaravelBootstrap\Data\DTOs\BootstrapCommand;

final class CustomBootstrapCommands implements ProvidesBootstrapCommands
{
    public function beforeMigrations(): array
    {
        return [
            // Generate TypeScript definitions before migrations
            BootstrapCommand::artisan(
                command: 'wayfinder:generate',
                message: 'Generating TypeScript routes and actions...',
                args: ['--path' => 'resources/js/wayfinder']
            ),
            
            // Run package manager type generation
            BootstrapCommand::packageManager(
                command: 'run zodgen',
                message: 'Generating Zod schemas...',
                args: []
            ),
        ];
    }

    public function afterMigrations(): array
    {
        return [
            // Generate model types after migrations (needs DB schema)
            BootstrapCommand::artisan(
                command: 'model:typer',
                message: 'Generating TypeScript types from models...',
                args: []
            ),
            
            // Optimize autoloader
            BootstrapCommand::composer(
                command: 'dump-autoload',
                message: 'Optimizing autoloader...',
                args: ['--optimize' => true]
            ),
        ];
    }
}
```

Register in `AppServiceProvider`:

```php
public function register(): void
{
    $this->app->singleton(
        \Igne\LaravelBootstrap\Contracts\ProvidesBootstrapCommands::class,
        \App\Bootstrap\CustomBootstrapCommands::class
    );
}
```
