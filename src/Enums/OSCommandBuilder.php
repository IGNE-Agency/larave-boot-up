<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Enums;

use Illuminate\Support\Facades\File;

final class OSCommandBuilder
{
    private string|null $command = null;
    private ?string $process = null;
    private ?string $version = null;
    private ?string $url = null;

    public function __construct(
        private readonly OSCommand $osCommand
    ) {
    }

    // ==================== Builder Methods ====================

    public function withCommand(string $command): self
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
            OSCommand::CHECK_PROCESS => $this->checkProcess($this->getProcess()),
            OSCommand::OPEN_TERMINAL => $this->openTerminal($this->getCommand()),
            OSCommand::OPEN_BROWSER => $this->openBrowser($this->getUrl()),
            OSCommand::START_DOCKER => $this->startDocker(),
            OSCommand::INSTALL_PHP => $this->installPhp($this->getVersion()),
            OSCommand::INSTALL_NODE => $this->installNode($this->getVersion()),
            OSCommand::INSTALL_COMPOSER => $this->installComposer(),
            OSCommand::INSTALL_BUN => $this->installBun($this->getVersion()),
            OSCommand::INSTALL_YARN => $this->installYarn($this->getVersion()),
            OSCommand::INSTALL_NPM => $this->installNpm($this->getVersion()),
            OSCommand::INSTALL_HOMEBREW => $this->installHomebrew(),
            OSCommand::INSTALL_DOCKER => $this->installDocker(),
            OSCommand::INSTALL_HERD => $this->installHerd(),
        };
    }

    // ==================== Getters & Helpers ====================

    private function getProcess(): string
    {
        return $this->process ?? '';
    }

    private function getCommand(): string
    {
        return $this->command ?? 'php artisan';
    }

    private function getVersion(): string
    {
        return $this->version ?? 'latest';
    }

    private function getUrl(): string
    {
        return $this->url ?? '';
    }

    private function isLatestVersion(string $version): bool
    {
        return $version === 'latest';
    }

    // ==================== Process Management ====================

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

    // ==================== Terminal & Browser Operations ====================

    private function openTerminal(string|array $command): string
    {
        $basePath = base_path();
        $cmd = is_array($command) ? implode(' ', $command) : $command;

        return match (PHP_OS_FAMILY) {
            'Darwin' => $this->openTerminalMacOS($basePath, $cmd),
            'Windows' => "start cmd /k \"cd /d {$basePath} && {$cmd}\"",
            'Linux' => $this->openTerminalLinux($basePath, $cmd),
            default => '',
        };
    }

    private function openTerminalMacOS(string $basePath, string $command): string
    {
        $escapedCommand = str_replace('"', '\\"', $command);

        return sprintf(
            "osascript -e 'tell app \"Terminal\" to do script \"cd %s && %s\"'",
            $basePath,
            $escapedCommand
        );
    }

    private function openTerminalLinux(string $basePath, string $command): string
    {
        $terminal = File::exists('/usr/bin/gnome-terminal') ? 'gnome-terminal' : 'xterm';
        $flag = $terminal === 'gnome-terminal' ? '--' : '-e';

        return "{$terminal} {$flag} bash -c 'cd {$basePath} && {$command}; exec bash'";
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

    // ==================== Docker Operations ====================

    private function startDocker(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => 'open -a Docker',
            'Windows' => 'start "" "C:\\Program Files\\Docker\\Docker\\Docker Desktop.exe"',
            default => 'systemctl --user start docker || sudo systemctl start docker',
        };
    }

    // ==================== Tool Installations ====================

    private function installPhp(string $version): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => $this->installWithBrewIfNeeded('php', $version),
            'Windows' => $this->installWithWinget('shivammathur.php', $version),
            default => $this->installPhpLinux($version),
        };
    }

    private function installPhpLinux(string $version): string
    {
        $phpVersion = $this->isLatestVersion($version) ? 'php' : "php{$version}";
        $extensions = ['cli', 'mbstring', 'xml', 'zip'];
        $packages = array_map(fn($ext) => "{$phpVersion}-{$ext}", $extensions);

        return 'sudo apt-get update && sudo apt-get install -y ' . $phpVersion . ' ' . implode(' ', $packages);
    }

    private function installBun(string $version): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'powershell -c "irm bun.sh/install.ps1|iex';
            return $this->isLatestVersion($version) ? $cmd . '"' : $cmd . "; bun upgrade --version {$version}\"";
        }

        $cmd = 'curl -fsSL https://bun.sh/install | bash';
        return $this->isLatestVersion($version) ? $cmd : $cmd . " -s \"bun-v{$version}\"";
    }

    private function installNode(string $version): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => $this->installWithBrewIfNeeded('node', $version),
            'Windows' => $this->installWithWinget('OpenJS.NodeJS', $version),
            default => $this->installNodeLinux($version),
        };
    }

    private function installNodeLinux(string $version): string
    {
        $setupVersion = $this->isLatestVersion($version) ? 'lts' : $version;
        return "curl -fsSL https://deb.nodesource.com/setup_{$setupVersion}.x | sudo -E bash - && sudo apt-get install -y nodejs";
    }

    private function installComposer(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'powershell -c "Invoke-WebRequest -Uri https://getcomposer.org/installer -OutFile composer-setup.php; ' .
                'php composer-setup.php --install-dir=%USERPROFILE%\\AppData\\Roaming\\Composer --filename=composer.bat; ' .
                'Remove-Item composer-setup.php"';
        }

        return 'curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer';
    }

    private function installYarn(string $version): string
    {
        return $this->installNpmPackage('yarn', $version);
    }

    private function installNpm(string $version): string
    {
        return $this->installNpmPackage('npm', $version, true);
    }

    private function installNpmPackage(string $package, string $version, bool $useLatestSuffix = false): string
    {
        $versionSuffix = $this->isLatestVersion($version)
            ? ($useLatestSuffix ? '@latest' : '')
            : "@{$version}";

        return "npm install -g {$package}{$versionSuffix}";
    }

    // ==================== Package Manager Helpers ====================

    private function installHomebrew(): string
    {
        return \in_array(PHP_OS_FAMILY, ['Darwin', 'Linux'])
            ? '/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"'
            : '';
    }

    private function installWithBrewIfNeeded(string $package, string $version): string
    {
        $versionSuffix = $this->isLatestVersion($version) ? '' : "@{$version}";
        $brewInstall = $this->installHomebrew();

        return "command -v brew > /dev/null 2>&1 || {$brewInstall}; brew install {$package}{$versionSuffix}";
    }

    private function installWithWinget(string $package, string $version): string
    {
        $versionFlag = $this->isLatestVersion($version) ? '' : " --version {$version}";
        return "winget install {$package}{$versionFlag}";
    }

    // ==================== Runner Installations ====================

    private function installHerd(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => $this->installWithBrewIfNeeded('herd', 'latest'),
            'Windows' => throw new \RuntimeException(
                'Laravel Herd for Windows requires manual installation with administrator privileges. ' .
                'Please download the installer from: https://herd.laravel.com/windows ' .
                'After installation, you may need to add %USERPROFILE%\.config\herd to Windows Defender exclusions for better performance.'
            ),
            default => throw new \RuntimeException(
                'Laravel Herd is only available for macOS 12.0+ and Windows 10+. ' .
                'Please download it manually from: https://herd.laravel.com ' .
                'For Linux, consider using Laravel Sail (Docker) or the built-in Laravel development server.'
            ),
        };
    }

    private function installDocker(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => $this->installWithBrewIfNeeded('docker', 'latest'),
            'Windows' => 'winget install Docker.DockerDesktop',
            default => 'curl -fsSL https://get.docker.com | sh && sudo usermod -aG docker $USER',
        };
    }
}
