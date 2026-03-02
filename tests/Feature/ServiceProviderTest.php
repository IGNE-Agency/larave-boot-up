<?php

declare(strict_types=1);

test('service provider is loaded', function () {
    expect(app()->getProvider('Igne\LaravelBootstrap\BootstrapServiceProvider'))
        ->not->toBeNull();
});

test('bootstrap config is available', function () {
    expect(config('bootstrap'))->toBeArray();
    expect(config('bootstrap.runner'))->toBeArray();
    expect(config('bootstrap.queue'))->toBeArray();
    expect(config('bootstrap.database'))->toBeArray();
});

test('config has expected structure', function () {
    $config = config('bootstrap');
    
    expect($config)->toHaveKeys([
        'runner',
        'package_manager',
        'tools',
        'auto_install',
        'database',
        'queue',
        'shutdown',
    ]);
});
