<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Enums;

enum CLIToolOption: string
{
    case ARTISAN = 'artisan';
    case COMPOSER = 'composer';
    case PACKAGE_MANAGER = 'package_manager';
}
