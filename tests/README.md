# Testing Laravel Bootstrap Package

This directory contains tests for the Laravel Bootstrap package using **Pest** and **Orchestra Testbench**.

## Running Tests

### Install Dependencies

First, ensure all dependencies are installed:

```bash
composer install
```

### Run All Tests

```bash
./vendor/bin/pest
```

Or using composer:

```bash
composer test
```

### Run Specific Test Suites

```bash
# Run only feature tests
./vendor/bin/pest tests/Feature

# Run only unit tests
./vendor/bin/pest tests/Unit

# Run a specific test file
./vendor/bin/pest tests/Feature/DeploymentTest.php
```

### Run with Coverage

```bash
./vendor/bin/pest --coverage
```

## Test Structure

```
tests/
├── Feature/           # Integration tests
│   ├── DeploymentTest.php
│   └── ServiceProviderTest.php
├── Unit/             # Unit tests
│   └── EnumTest.php
├── Pest.php          # Pest configuration
└── TestCase.php      # Base test case
```

## Writing Tests

### Feature Tests

Feature tests verify that multiple components work together correctly:

```php
test('queue worker starts when auto_start is enabled', function () {
    config()->set('bootstrap.queue.auto_start', true);
    
    // Your test logic here
});
```

### Unit Tests

Unit tests verify individual components in isolation:

```php
test('package manager enum has expected values', function () {
    expect(PackageManager::cases())->toHaveCount(3);
});
```

## Testing Without Installing in Another Project

This test suite uses **Orchestra Testbench**, which simulates a full Laravel application environment. This means you can:

1. Test all package functionality locally
2. Test service provider registration
3. Test configuration loading
4. Test commands and pipelines
5. Mock external dependencies

No need to install the package in a separate Laravel project for testing!

## Configuration

The `TestCase.php` provides a simulated Laravel environment with:

- SQLite in-memory database
- Bootstrap package configuration
- Service provider registration
- Test-specific environment variables

## Mocking

Use Mockery for mocking dependencies:

```php
$manager = Mockery::mock(ExternalCommandManager::class);
$manager->shouldReceive('call')->once();
```

## Continuous Integration

Add this to your CI pipeline (GitHub Actions example):

```yaml
- name: Run tests
  run: ./vendor/bin/pest --coverage
```
