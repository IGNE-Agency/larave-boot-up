<?php

return [
    'server' => [
        'default' => env('BOOTSTRAP_SERVER', null),
        'prompt' => env('BOOTSTRAP_PROMPT_SERVER', true),
    ],

    'package_manager' => [
        'default' => env('BOOTSTRAP_PACKAGE_MANAGER', 'bun'),
        'available' => ['bun', 'yarn', 'npm'],
    ],

    'tools' => [
        'php' => env('PHP_VERSION', 'latest'),
        'node' => env('NODE_VERSION', 'latest'),
        'composer' => env('COMPOSER_VERSION', 'latest'),
        'bun' => env('BUN_VERSION', 'latest'),
        'yarn' => env('YARN_VERSION', 'latest'),
        'npm' => env('NPM_VERSION', 'latest'),
    ],

    'auto_install' => [
        'enabled' => env('BOOTSTRAP_AUTO_INSTALL', true),
        'tools' => ['php', 'node', 'composer'],
    ],

    'database' => [
        'auto_create' => env('BOOTSTRAP_AUTO_CREATE_DB', true),
        'prompt_credentials' => env('BOOTSTRAP_PROMPT_DB_CREDENTIALS', true),
    ],

    'queue' => [
        'auto_start' => env('BOOTSTRAP_AUTO_START_QUEUE', true),
        'separate_terminal' => env('BOOTSTRAP_QUEUE_SEPARATE_TERMINAL', true),
        'connection' => env('QUEUE_CONNECTION', 'database'),
    ],

    'migrations' => [
        'auto_run' => env('BOOTSTRAP_AUTO_RUN_MIGRATIONS', true),
    ],

    'shutdown' => [
        'prompt_server_stop' => env('BOOTSTRAP_PROMPT_SERVER_STOP', true),
        'default_stop_server' => env('BOOTSTRAP_DEFAULT_STOP_SERVER', false),
    ],

    'deploy' => [
        'separate_terminal' => env('BOOTSTRAP_DEPLOY_SEPARATE_TERMINAL', true),
        'enable_caching' => env('BOOTSTRAP_ENABLE_CACHING', true),
    ],
];
