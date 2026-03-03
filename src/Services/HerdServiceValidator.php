<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Services;

use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Enums\ExternalCommandRunner;
use Igne\LaravelBootstrap\Enums\OSCommand;
use Igne\LaravelBootstrap\Exceptions\DependencyCheckException;

final readonly class HerdServiceValidator
{
    public function validate(InterruptibleCommand $command): void
    {
        $command->info('Validating Herd services...');

        $this->stopValetIfRunning($command);
        $this->ensureHerdServicesRunning($command);

        $command->info('✅ Herd services are running correctly.');
    }

    private function stopValetIfRunning(InterruptibleCommand $command): void
    {
        if (!$this->isValetRunning($command)) {
            return;
        }

        $command->warn('Valet is running and conflicts with Herd. Stopping Valet services...');
        $this->stopValetServices($command);
        $command->info('Valet services stopped successfully.');
    }

    private function isValetRunning(InterruptibleCommand $command): bool
    {
        if (!$command->externalProcessManager->isCommandAvailable('valet')) {
            return false;
        }

        $checkCommand = OSCommand::CHECK_PROCESS->forProcess('nginx')->execute();
        $nginxRunning = $command->externalProcessManager->isCommandRunning($checkCommand);

        $checkPhpFpm = OSCommand::CHECK_PROCESS->forProcess('php-fpm')->execute();
        $phpFpmRunning = $command->externalProcessManager->isCommandRunning($checkPhpFpm);

        return $nginxRunning || $phpFpmRunning;
    }

    private function stopValetServices(InterruptibleCommand $command): void
    {
        $command->externalProcessManager->callSilent('valet stop');
        sleep(2);

        $services = ['nginx', 'php-fpm', 'dnsmasq'];
        foreach ($services as $service) {
            $this->forceStopService($service, $command);
        }
    }

    private function forceStopService(string $service, InterruptibleCommand $command): void
    {
        $checkCommand = OSCommand::CHECK_PROCESS->forProcess($service)->execute();
        if ($command->externalProcessManager->isCommandRunning($checkCommand)) {
            $command->externalProcessManager->callSilent("sudo brew services stop {$service}");
        }
    }

    private function ensureHerdServicesRunning(InterruptibleCommand $command): void
    {
        if ($this->areAllHerdServicesRunning($command)) {
            $command->info('Herd services are already running correctly.');
            return;
        }

        $this->startHerdServices($command);

        if (!$this->areAllHerdServicesRunning($command)) {
            $command->warn('Herd services not running correctly. Attempting restart...');
            $this->restartHerdServices($command);

            if (!$this->areAllHerdServicesRunning($command)) {
                throw new DependencyCheckException(
                    'Failed to start Herd services correctly. Please check Herd status manually with: herd status'
                );
            }

            $command->info('Herd services restarted successfully.');
        }
    }

    private function areAllHerdServicesRunning(InterruptibleCommand $command): bool
    {
        $herd = ExternalCommandRunner::HERD->command();
        $statusProcess = $command->externalProcessManager->callSilent("{$herd} status");

        if (!$statusProcess->isSuccessful()) {
            return false;
        }

        $output = $statusProcess->getOutput();
        $requiredServices = ['nginx', 'php', 'dnsmasq'];

        foreach ($requiredServices as $service) {
            if (!$this->isServiceRunningInOutput($output, $service)) {
                return false;
            }
        }

        return true;
    }

    private function isServiceRunningInOutput(string $output, string $service): bool
    {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (stripos($line, $service) !== false) {
                return stripos($line, 'running') !== false || stripos($line, 'started') !== false;
            }
        }

        return false;
    }

    private function startHerdServices(InterruptibleCommand $command): void
    {
        $herd = ExternalCommandRunner::HERD->command();
        $command->externalProcessManager->call("{$herd} start");
        sleep(3);
    }

    private function restartHerdServices(InterruptibleCommand $command): void
    {
        $herd = ExternalCommandRunner::HERD->command();
        $command->externalProcessManager->callSilent("{$herd} stop");
        sleep(2);
        $command->externalProcessManager->callSilent("{$herd} restart");
        sleep(3);
    }
}
