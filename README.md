# Laravel Bootstrap

A comprehensive Laravel application bootstrap package for local development with automatic dependency management, database setup, and multi-runner support.

## Features

- **Multi-Runner Support**: Herd, Sail, or Laravel built-in server
- **Automatic Dependency Installation**: Auto-installs missing tools (bun, composer, node, etc.)
- **Smart Version Management**: Support for specific versions or 'latest' for safe updates
- **Database Auto-Setup**: Interactive database creation and credential management
- **Queue Management**: Automatic queue worker in separate terminal
- **Migration Auto-Run**: Runs migrations automatically when available
- **Interactive Prompts**: User-friendly setup experience
- **Configurable Package Managers**: Choose between bun (default), yarn, or npm
- **DRY, SOLID, KISS**: Clean, maintainable codebase following best practices

## Installation

Install as a development dependency:

```bash
composer require igne-agency/laravel-bootstrap --dev
```

The package will auto-register via Laravel's package discovery.

> **Important:** Always use the `--dev` flag to ensure this package is only installed in development environments and excluded from production deployments.

## Quick Start

```bash
# Start your application (will prompt for runner if not specified)
php artisan app:serve

# Or specify a runner directly
php artisan app:serve herd
php artisan app:serve sail
php artisan app:serve laravel

# With options
php artisan app:serve herd --seed --update

# Stop your application
php artisan app:down
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=bootstrap-config
```

This creates `config/bootstrap.php` where you can configure all aspects of the package.

### Configuration Options

#### Runner Configuration

```php
'runner' => [
    'default' => env('BOOTSTRAP_RUNNER', null), // null = always prompt
    'prompt' => env('BOOTSTRAP_PROMPT_RUNNER', true),
],
```

#### Package Manager

```php
'package_manager' => [
    'default' => env('BOOTSTRAP_PACKAGE_MANAGER', 'bun'),
    'available' => ['bun', 'yarn', 'npm'],
],
```

#### Tool Versions

```php
'tools' => [
    'php' => env('PHP_VERSION', 'latest'),
    'node' => env('NODE_VERSION', 'latest'),
    'composer' => env('COMPOSER_VERSION', 'latest'),
    'bun' => env('BUN_VERSION', 'latest'),
    'yarn' => env('YARN_VERSION', 'latest'),
    'npm' => env('NPM_VERSION', 'latest'),
],
```

Use `'latest'` for automatic safe version updates, or specify exact versions like `'20.11.0'`.

#### Auto-Installation

```php
'auto_install' => [
    'enabled' => env('BOOTSTRAP_AUTO_INSTALL', true),
    'tools' => ['php', 'node', 'composer', 'bun'],
],
```

#### Database Management

```php
'database' => [
    'auto_create' => env('BOOTSTRAP_AUTO_CREATE_DB', true),
    'prompt_credentials' => env('BOOTSTRAP_PROMPT_DB_CREDENTIALS', true),
],
```

#### Queue Configuration

```php
'queue' => [
    'auto_start' => env('BOOTSTRAP_AUTO_START_QUEUE', true),
    'separate_terminal' => env('BOOTSTRAP_QUEUE_SEPARATE_TERMINAL', true),
    'connection' => env('QUEUE_CONNECTION', 'database'),
],
```

#### Shutdown Behavior

```php
'shutdown' => [
    'prompt_runner_stop' => env('BOOTSTRAP_PROMPT_RUNNER_STOP', true),
    'default_stop_runner' => env('BOOTSTRAP_DEFAULT_STOP_RUNNER', false),
],
```

## Usage

### Starting Your Application

```bash
php artisan app:serve {runner?} {--seed} {--migrate} {--update} {--no-frontend}
```

**Arguments:**

- `runner` (optional): `herd`, `sail`, or `laravel`

**Options:**

- `--seed`: Seed the database after migrations
- `--migrate`: Run migrations (default: true)
- `--update`: Update dependencies before starting
- `--no-frontend`: Skip frontend asset building

**What happens:**

1. Interactive runner selection (if not specified)
2. Dependency checking and auto-installation
3. Database credential prompts (if needed)
4. Database creation (if doesn't exist)
5. Dependency installation (composer, npm/yarn/bun)
6. Migration execution
7. Queue worker startup (in separate terminal)
8. Application boot

### Stopping Your Application

```bash
php artisan app:down
```

Interactive prompt asks whether to:

- Stop only running processes (default)
- Stop the runner itself (Herd/Sail)

## Examples

### First-Time Setup

```bash
# Clone your Laravel project
git clone https://github.com/yourname/project.git
cd project

# Install composer dependencies
composer install

# Start the application
php artisan app:serve
```

The package will:

- Prompt for runner selection
- Check for missing tools (node, bun, etc.)
- Install missing tools automatically
- Prompt for database credentials if missing
- Create database if it doesn't exist
- Run migrations
- Start queue workers
- Boot your application

### Using Specific Versions

In your `.env`:

```env
PHP_VERSION=8.4
NODE_VERSION=20.11.0
BUN_VERSION=1.0.25
COMPOSER_VERSION=2.7.0
```

Or use `latest` for automatic safe updates:

```env
PHP_VERSION=latest
NODE_VERSION=latest
BUN_VERSION=latest
COMPOSER_VERSION=latest
```

### Different Runners

**Laravel Herd:**

```bash
php artisan app:serve herd
```

**Laravel Sail:**

```bash
php artisan app:serve sail
```

**Laravel Built-in Server:**

```bash
php artisan app:serve laravel
```

### Environment-Specific Configuration

**Development (auto-install everything):**

```env
BOOTSTRAP_AUTO_INSTALL=true
BOOTSTRAP_AUTO_CREATE_DB=true
BOOTSTRAP_AUTO_RUN_MIGRATIONS=true
BOOTSTRAP_AUTO_START_QUEUE=true
```

**Production-like (manual control):**

```env
BOOTSTRAP_AUTO_INSTALL=false
BOOTSTRAP_AUTO_CREATE_DB=false
BOOTSTRAP_PROMPT_DB_CREDENTIALS=false
BOOTSTRAP_AUTO_START_QUEUE=false
```

## Advanced Features

### Automatic Tool Installation

When enabled, the package automatically installs missing tools:

- Detects missing dependencies
- Downloads and installs them
- Verifies versions
- Updates to latest safe versions if configured

### Smart Version Management

The `'latest'` version option:

- Checks for the latest stable/LTS version
- Only updates to safe, stable releases
- Caches version checks to avoid API rate limits
- Falls back to sensible defaults if API unavailable

### Database Auto-Creation

If database doesn't exist:

1. Prompts for credentials (if not in .env)
2. Updates .env file with credentials
3. Creates database with proper charset/collation
4. Runs migrations automatically

### Queue Worker Management

Queue workers run in separate terminal windows:

- **macOS**: Opens new Terminal.app window
- **Linux**: Opens new gnome-terminal window
- **Fallback**: Runs in background if terminal unavailable

## Troubleshooting

### Database Connection Issues

If you see database connection errors:

1. Check `.env` file has correct credentials
2. Ensure MySQL/PostgreSQL is running
3. Verify database exists
4. Run `php artisan config:clear`

### Tool Installation Failures

If auto-installation fails:

1. Check internet connection
2. Verify system permissions
3. Install tools manually
4. Disable auto-install: `BOOTSTRAP_AUTO_INSTALL=false`

### Queue Worker Not Starting

If queue worker doesn't start:

1. Check queue connection in `.env`
2. Verify database tables exist
3. Manually run: `php artisan queue:work`

### Sail-Specific Issues

For Sail users:

1. Ensure Docker is installed and running
2. Check Docker has sufficient resources
3. Verify ports 80/3306 are available
4. Run `sail up -d` manually if needed

## Requirements

- PHP 8.4+
- Laravel 12.x
- Composer 2.x

## Production Safety

This package is **automatically excluded** from production when installed with `--dev`. Composer will not install dev dependencies when you run:

```bash
composer install --no-dev
```

This ensures the package and its commands are never available in production environments.

### Environment Detection

The package includes safety checks to prevent accidental use in non-local environments. Commands will refuse to run if:

- `APP_ENV` is set to `production`, `staging` or `development`
- The environment is detected as a remote server

## License

MIT License - see [LICENSE](LICENSE) for details.

## Credits

Created by [Rick Blanksma](https://github.com/rickblanksma) at [IGNE](https://igne.nl)

## Support

For issues, questions, or contributions, please use the [GitHub issue tracker](https://github.com/igne/laravel-bootstrap/issues).
