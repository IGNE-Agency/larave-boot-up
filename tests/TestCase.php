<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Tests;

use Igne\LaravelBootstrap\BootstrapServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BootstrapServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('bootstrap', [
            'runner' => [
                'default' => null,
                'prompt' => true,
            ],
            'package_manager' => [
                'default' => 'bun',
                'available' => ['bun', 'yarn', 'npm'],
            ],
            'tools' => [
                'php' => 'latest',
                'node' => 'latest',
                'composer' => 'latest',
                'bun' => 'latest',
                'yarn' => 'latest',
                'npm' => 'latest',
            ],
            'auto_install' => [
                'enabled' => true,
                'tools' => ['php', 'node', 'composer', 'bun'],
            ],
            'database' => [
                'auto_create' => true,
                'prompt_credentials' => true,
            ],
            'queue' => [
                'auto_start' => true,
                'separate_terminal' => true,
                'connection' => 'database',
            ],
            'shutdown' => [
                'prompt_runner_stop' => true,
                'default_stop_runner' => false,
            ],
        ]);
    }
}
