<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Repositories;

use Illuminate\Support\Facades\File;

final class ProcessFileRepository
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = storage_path('framework/bootstrap-processes.json');
    }

    public function save(array $processes): void
    {
        $this->ensureDirectoryExists();
        File::put($this->filePath, json_encode($processes, JSON_PRETTY_PRINT));
    }

    public function load(): array
    {
        if (! File::exists($this->filePath)) {
            return [];
        }

        $content = File::get($this->filePath);
        $processes = json_decode($content, true);

        return \is_array($processes) ? $processes : [];
    }

    public function clear(): void
    {
        if (File::exists($this->filePath)) {
            File::delete($this->filePath);
        }
    }

    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->filePath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}
