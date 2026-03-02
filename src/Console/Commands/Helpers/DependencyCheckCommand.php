<?php

namespace Igne\LaravelBootstrap\Console\Commands\Helpers;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;
use Igne\LaravelBootstrap\Services\ToolInstaller;
use Igne\LaravelBootstrap\Services\VersionChecker;
use Illuminate\Contracts\Console\Isolatable;

final class DependencyCheckCommand extends InterruptibleCommand implements Isolatable
{
    protected $signature = 'check:dependencies {runner : The runner to use (herd, sail, laravel)}';

    protected $description = 'Make sure the dependencies is correct for development';

    protected $hidden = true;

    protected ToolInstaller $installer;

    protected VersionChecker $versionChecker;

    public function __construct()
    {
        parent::__construct();
        $this->installer = new ToolInstaller();
        $this->versionChecker = new VersionChecker();
    }

    public function handleWithInterrupts(): int
    {
        $this->info('Checking dependencies...');
        try {
            $this->ensureEnvFileExists()
                ->generateAppKeyIfMissing()
                ->validateDependencies();
        } catch (\Throwable $e) {
            throw new DependencyCheckException($e->getMessage(), $e->getCode(), $e);
        }
        $this->info('All dependencies are correct.');

        return self::SUCCESS;
    }

    public function cleanup(int $signal): void
    {
        $this->info('Cleaning up dependency check...');
        $this->command->stopAllProcesses();
    }

    protected function ensureEnvFileExists(): self
    {
        $env = base_path('.env');
        $example = base_path('.env.example');

        if (!file_exists($env) && file_exists($example)) {
            copy($example, $env);
            $this->info('.env copied from .env.example');
        } elseif (file_exists($env)) {
            $this->info('.env already exists, skipping.');
        } else {
            throw new DependencyCheckException('No .env or .env.example found. Please create one.');
        }

        return $this;
    }

    protected function generateAppKeyIfMissing(): self
    {
        if (empty(config('app.key')) || config('app.key') === 'base64:') {
            $this->info('Generating application key...');
            $this->call('key:generate', ['--force' => true]);
        }

        return $this;
    }

    public function validateDependencies(): void
    {
        $autoInstall = config('bootstrap.auto_install.enabled', true);
        $tools = config('bootstrap.auto_install.tools', ['php', 'node', 'composer']);

        $packageManager = config('bootstrap.package_manager.default', 'bun');
        $allTools = array_merge($tools, [$packageManager]);

        foreach ($allTools as $tool) {
            $this->validateTool($tool, $autoInstall);
        }
    }

    protected function validateTool(string $tool, bool $autoInstall): void
    {
        $requiredVersion = config("bootstrap.tools.{$tool}", 'latest');

        if (!$this->command->isCommandAvailable($tool)) {
            if ($autoInstall) {
                $this->warn("{$tool} not found. Installing...");
                $this->installer->install($tool, $requiredVersion, $this->output);
            } else {
                throw new DependencyCheckException("{$tool} not found. Please install it manually.");
            }
        }

        $currentVersion = $this->getVersion($tool);

        if ($requiredVersion === 'latest') {
            $latestVersion = $this->versionChecker->getLatestSafeVersion($tool);

            if (version_compare($currentVersion, $latestVersion, '<')) {
                if ($autoInstall) {
                    $this->warn("{$tool} {$currentVersion} is outdated. Updating to {$latestVersion}...");
                    $this->installer->update($tool, $latestVersion, $this->output);
                } else {
                    $this->warn("{$tool} {$currentVersion} is outdated. Latest: {$latestVersion}");
                }
            } else {
                $this->line("{$tool} {$currentVersion} OK (latest).");
            }
        } else {
            if (version_compare($currentVersion, $requiredVersion, '<')) {
                if ($autoInstall) {
                    $this->warn("{$tool} {$currentVersion} too old. Installing {$requiredVersion}...");
                    $this->installer->update($tool, $requiredVersion, $this->output);
                } else {
                    throw new DependencyCheckException("{$tool} {$currentVersion} too old. Required: >= {$requiredVersion}");
                }
            } else {
                $this->line("{$tool} {$currentVersion} OK.");
            }
        }
    }

    private function getVersion(string $command): string
    {
        $process = $this->command->callSilent("{$command} -v");
        $output = $process->getOutput();
        $output = trim($output);
        $output = ltrim($output, 'v');
        preg_match('/\d+(\.\d+)+/', $output, $matches);

        return $matches[0] ?? '0.0.0';
    }
}
