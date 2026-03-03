<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Enums;

enum CommandEnvironment: string
{
    case ARTISAN = 'artisan';
    case COMPOSER = 'composer';
    case PACKAGE_MANAGER = 'package_manager';
}
