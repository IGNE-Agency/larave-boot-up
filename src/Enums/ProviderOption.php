<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Enums;

enum ProviderOption: string
{
    case APP_SERVE = 'bootstrap.serve';
    case APP_DEPLOY = 'bootstrap.deploy';
    case DATABASE = 'bootstrap.database';
    case DEPENDENCIES = 'bootstrap.dependencies';
}
