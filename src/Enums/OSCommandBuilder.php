<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Enums;

use Igne\LaravelBootstrap\Console\ExternalCommand;
use Symfony\Component\Console\Command\Command;
use Illuminate\Support\Facades\File;

final class OSCommandBuilder
{
    private ExternalCommand|Command|string|null $command = null;

    private ?string $process = null;

    private ?string $version = null;

    private ?string $url = null;

    public function __construct(
        private readonly OSCommand $osCommand
    ) {
    }

    public function withCommand(ExternalCommand|Command|string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function forProcess(string $process): self
    {
        $this->process = $process;

        return $this;
    }

    public function forVersion(string $version = 'latest'): self
    {
        $this->version = $version;

        return $this;
    }

    public function forUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function execute(): string
    {
        return match ($this->osCommand) {
            OSCommand::KILL_PHP_ARTISAN => $this->killPhpArtisan(),
            OSCommand::CHECK_PROCESS => $this->checkProcess($this->process ?? ''),
            OSCommand::OPEN_TERMINAL => $this->openTerminal($this->command ?? 'php artisan'),
            OSCommand::START_DOCKER => $this->startDocker(),
            OSCommand::INSTALL_BUN => $this->installBun($this->version ?? 'latest'),
            OSCommand::INSTALL_NODE => $this->installNode($this->version ?? 'latest'),
            OSCommand::INSTALL_COMPOSER => $this->installComposer(),
            OSCommand::INSTALL_YARN => $this->installYarn($this->version ?? 'latest'),
            OSCommand::INSTALL_NPM => $this->installNpm($this->version ?? 'latest'),
            OSCommand::INSTALL_HOMEBREW => $this->installHomebrew(),
            OSCommand::OPEN_BROWSER => $this->openBrowser($this->url ?? ''),
        };
    }

    private function killPhpArtisan(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'taskkill /F /IM php.exe /FI "WINDOWTITLE eq *artisan serve*"',
            default => 'pkill -f "php artisan serve"',
        };
    }

    private function checkProcess(string $process): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => "tasklist /FI \"IMAGENAME eq {$process}.exe\" /NH",
            default => "pgrep -f {$process}",
        };
    }

    private function openTerminal(string|array $command): string
    {
        $basePath = base_path();
        $commandString = is_array($command) ? implode(' ', $command) : $command;

        return match (PHP_OS_FAMILY) {
            'Darwin' => "osascript -e 'tell app \"Terminal\" to do script \"cd {$basePath} && {$commandString}\"'",
            'Windows' => "start cmd /k \"cd /d {$basePath} && {$commandString}\"",
            'Linux' => File::exists('/usr/bin/gnome-terminal')
            ? "gnome-terminal -- bash -c 'cd {$basePath} && {$commandString}; exec bash'"
            : "xterm -e 'cd {$basePath} && {$commandString}; exec bash'",
            default => '',
        };
    }

    private function startDocker(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => 'open -a Docker',
            'Windows' => 'start "" "C:\\Program Files\\Docker\\Docker\\Docker Desktop.exe"',
            default => 'systemctl --user start docker || sudo systemctl start docker',
        };
    }

    private function installBun(string $version): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => $version === 'latest'
            ? 'powershell -c "irm bun.sh/install.ps1|iex"'
            : "powershell -c \"irm bun.sh/install.ps1|iex; bun upgrade --version {$version}\"",
            default => $version === 'latest'
            ? 'curl -fsSL https://bun.sh/install | bash'
            : "curl -fsSL https://bun.sh/install | bash -s \"bun-v{$version}\"",
        };
    }

    private function installNode(string $version): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => $this->installNodeMacOS($version),
            'Windows' => $version === 'latest'
            ? 'winget install OpenJS.NodeJS'
            : "winget install OpenJS.NodeJS --version {$version}",
            default => $version === 'latest'
            ? 'curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash - && sudo apt-get install -y nodejs'
            : "curl -fsSL https://deb.nodesource.com/setup_{$version}.x | sudo -E bash - && sudo apt-get install -y nodejs",
        };
    }

    private function installNodeMacOS(string $version): string
    {
        $brewCheck = 'command -v brew > /dev/null 2>&1';
        $brewInstall = $this->installHomebrew();
        $nodeInstall = $version === 'latest'
            ? 'brew install node'
            : "brew install node@{$version}";

        return "{$brewCheck} || {$brewInstall}; {$nodeInstall}";
    }

    private function installComposer(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'powershell -c "Invoke-WebRequest -Uri https://getcomposer.org/installer -OutFile composer-setup.php; php composer-setup.php --install-dir=%USERPROFILE%\\AppData\\Roaming\\Composer --filename=composer.bat; Remove-Item composer-setup.php"',
            'Darwin' => 'curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer',
            default => 'curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer',
        };
    }

    private function installYarn(string $version): string
    {
        return $version === 'latest'
            ? 'npm install -g yarn'
            : "npm install -g yarn@{$version}";
    }

    private function installNpm(string $version): string
    {
        return $version === 'latest'
            ? 'npm install -g npm@latest'
            : "npm install -g npm@{$version}";
    }

    private function installHomebrew(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"',
            'Linux' => '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"',
            default => '',
        };
    }

    private function openBrowser(string $url): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => "open {$url}",
            'Windows' => "start {$url}",
            'Linux' => "xdg-open {$url} || sensible-browser {$url} || x-www-browser {$url}",
            default => '',
        };
    }
}
