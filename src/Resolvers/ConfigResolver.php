<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Resolvers;

final class ConfigResolver
{
    public function shouldAutoOpenBrowser(): bool
    {
        return config('bootstrap.browser.auto_open', true);
    }

    public function shouldSkipFrontend(): bool
    {
        return config('bootstrap.assets.skip', false);
    }

    public function shouldWatchAssets(): bool
    {
        $watchInDev = config('bootstrap.assets.watch_in_dev', true);
        $isDev = app()->environment('local', 'development');

        return $watchInDev && $isDev;
    }

    public function shouldUseSeparateTerminal(): bool
    {
        return config('bootstrap.assets.separate_terminal', true);
    }

    public function isAutoInstallEnabled(): bool
    {
        return config('bootstrap.auto_install.enabled', true);
    }
}
