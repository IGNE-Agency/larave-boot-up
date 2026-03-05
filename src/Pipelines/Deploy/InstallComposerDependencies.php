<?php

declare(strict_types=1);

namespace Igne\LaravelBootstrap\Pipelines\Deploy;

use Closure;
use Exception;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Detectors\LockFileSyncIssueDetector;
use Igne\LaravelBootstrap\Managers\ComposerDependencyManager;
use Igne\LaravelBootstrap\Verifiers\ComposerJsonPresenceVerifier;

final readonly class InstallComposerDependencies
{
    public function __construct(
        private ComposerJsonPresenceVerifier $presenceVerifier,
        private LockFileSyncIssueDetector $issueDetector
    ) {}

    public function handle(InterruptibleCommand $command, Closure $next): InterruptibleCommand
    {
        $verifier = new ComposerJsonPresenceVerifier($command->getOutput());
        $verifier->validate();

        $this->manageDependencies($command);

        return $next($command);
    }

    private function manageDependencies(InterruptibleCommand $command): void
    {
        $manager = $this->createDependencyManager($command);

        if ($this->shouldUpdateDependencies($command)) {
            $manager->update();

            return;
        }

        $this->installWithRetry($command, $manager);
    }

    private function createDependencyManager(InterruptibleCommand $command): ComposerDependencyManager
    {
        return new ComposerDependencyManager(
            $command->externalProcessManager,
            $command->getOutput()
        );
    }

    private function shouldUpdateDependencies(InterruptibleCommand $command): bool
    {
        return $command->hasOption('update') && $command->option('update');
    }

    private function installWithRetry(InterruptibleCommand $command, ComposerDependencyManager $manager): void
    {
        try {
            $manager->install();
        } catch (Exception $exception) {
            $this->handleInstallException($command, $manager, $exception);
        }
    }

    private function handleInstallException(
        InterruptibleCommand $command,
        ComposerDependencyManager $manager,
        Exception $exception
    ): void {
        $detector = new LockFileSyncIssueDetector;

        if ($detector->isSyncIssue($exception->getMessage())) {
            $manager->regenerateLockFile();
            $manager->install();

            return;
        }

        throw $exception;
    }
}
