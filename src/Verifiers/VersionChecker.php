<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Verifiers;

use Igne\LaravelBootstrap\Contracts\ChecksVersions;
use Illuminate\Support\Facades\Http;

final class VersionChecker implements ChecksVersions
{
    protected array $cache = [];

    public function getLatestSafeVersion(string $tool): string
    {
        if (isset($this->cache[$tool])) {
            return $this->cache[$tool];
        }

        $version = match ($tool) {
            'bun' => $this->getLatestBunVersion(),
            'node' => $this->getLatestNodeVersion(),
            'composer' => $this->getLatestComposerVersion(),
            'yarn' => $this->getLatestYarnVersion(),
            'npm' => $this->getLatestNpmVersion(),
            'php' => $this->getLatestPhpVersion(),
            'docker' => $this->getLatestDockerVersion(),
            'herd' => $this->getLatestHerdVersion(),
            default => '0.0.0'
        };

        $this->cache[$tool] = $version;

        return $version;
    }

    protected function getLatestBunVersion(): string
    {
        try {
            $response = Http::timeout(5)->get('https://api.github.com/repos/oven-sh/bun/releases/latest');

            if ($response->successful()) {
                $tag = $response->json('tag_name');
                return ltrim($tag, 'v');
            }
        } catch (\Exception $e) {
        }

        return '1.0.0';
    }

    protected function getLatestNodeVersion(): string
    {
        try {
            $response = Http::timeout(5)->get('https://nodejs.org/dist/index.json');

            if ($response->successful()) {
                $versions = $response->json();
                $ltsVersions = array_filter($versions, fn($v) => isset($v['lts']) && $v['lts'] !== false);

                if (!empty($ltsVersions)) {
                    $latest = reset($ltsVersions);
                    return ltrim($latest['version'], 'v');
                }
            }
        } catch (\Exception $e) {
        }

        return '20.0.0';
    }

    protected function getLatestComposerVersion(): string
    {
        try {
            $response = Http::timeout(5)->get('https://api.github.com/repos/composer/composer/releases/latest');

            if ($response->successful()) {
                $tag = $response->json('tag_name');
                return ltrim($tag, 'v');
            }
        } catch (\Exception $e) {
        }

        return '2.0.0';
    }

    protected function getLatestYarnVersion(): string
    {
        try {
            $response = Http::timeout(5)->get('https://api.github.com/repos/yarnpkg/yarn/releases/latest');

            if ($response->successful()) {
                $tag = $response->json('tag_name');
                return ltrim($tag, 'v');
            }
        } catch (\Exception $e) {
        }

        return '1.22.0';
    }

    protected function getLatestNpmVersion(): string
    {
        try {
            $response = Http::timeout(5)->get('https://registry.npmjs.org/npm/latest');

            if ($response->successful()) {
                return $response->json('version');
            }
        } catch (\Exception $e) {
        }

        return '10.0.0';
    }

    protected function getLatestPhpVersion(): string
    {
        try {
            $response = Http::timeout(5)->get('https://www.php.net/releases/index.php?json&version=8');

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['version'])) {
                    return $data['version'];
                }
            }
        } catch (\Exception $e) {
        }

        return '8.4.0';
    }

    protected function getLatestDockerVersion(): string
    {
        try {
            $response = Http::timeout(5)->get('https://api.github.com/repos/docker/docker-ce/releases/latest');

            if ($response->successful()) {
                $tag = $response->json('tag_name');
                return ltrim($tag, 'v');
            }
        } catch (\Exception $e) {
        }

        return '24.0.0';
    }

    protected function getLatestHerdVersion(): string
    {
        try {
            $response = Http::timeout(5)->get('https://herd.laravel.com/api/versions/latest');

            if ($response->successful()) {
                $version = $response->json('version');
                return ltrim($version, 'v');
            }
        } catch (\Exception $e) {
        }

        return '1.0.0';
    }
}
