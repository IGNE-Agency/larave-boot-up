<?php

declare(strict_types=1);

use Igne\LaravelBootstrap\Pipelines\Deploy\StartQueueWorker;
use Igne\LaravelBootstrap\Console\InterruptibleCommand;
use Igne\LaravelBootstrap\Console\ExternalCommandManager;

test('queue worker pipeline can be instantiated', function () {
    $pipeline = new StartQueueWorker();

    expect($pipeline)->toBeInstanceOf(StartQueueWorker::class);
});

test('queue worker respects auto_start configuration', function () {
    config()->set('bootstrap.queue.auto_start', false);

    $command = Mockery::mock(InterruptibleCommand::class);
    $command->shouldReceive('info')
        ->once()
        ->with('Queue worker auto-start disabled.');

    $pipeline = new StartQueueWorker();
    $next = fn($cmd) => $cmd;

    $result = $pipeline->handle($command, $next);

    expect($result)->toBe($command);
});

test('queue worker starts when auto_start is enabled', function () {
    config()->set('bootstrap.queue.auto_start', true);
    config()->set('bootstrap.queue.separate_terminal', false);
    config()->set('bootstrap.queue.connection', 'database');

    $manager = Mockery::mock(ExternalCommandManager::class);
    $manager->shouldReceive('call')
        ->once()
        ->with(['php', 'artisan', 'queue:work', 'database', '--daemon'])
        ->andReturn(Mockery::mock(\Symfony\Component\Process\Process::class));

    $command = Mockery::mock(InterruptibleCommand::class)->makePartial();
    $command->externalProcessManager = $manager;
    $command->shouldReceive('info')
        ->twice();

    $pipeline = new StartQueueWorker();
    $next = fn($cmd) => $cmd;

    $result = $pipeline->handle($command, $next);

    expect($result)->toBe($command);
});
