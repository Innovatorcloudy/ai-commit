<?php

declare(strict_types=1);

/**
 * This file is part of the guanguans/ai-commit.
 *
 * (c) guanguans <ityaozm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

use App\Commands\ConfigCommand;
use App\ConfigManager;
use App\Exceptions\RuntimeException;
use App\Exceptions\UnsupportedConfigActionException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;

it('can set config', function (): void {
    mockFileExists(false);

    $this->artisan(ConfigCommand::class, [
        'action' => 'set',
        'key' => 'foo.bar',
        'value' => 'bar',
    ])->assertSuccessful();

    $this->artisan(ConfigCommand::class, [
        'action' => 'set',
        'key' => 'foo.bar',
        'value' => 'bar',
        '--file' => repository_path(ConfigManager::NAME),
    ])->assertSuccessful();

    $this->artisan(ConfigCommand::class, [
        'action' => 'set',
        'key' => 'foo.bar',
        'value' => 'bar',
        '--global' => true,
    ])->assertSuccessful();

    $this->artisan(ConfigCommand::class, [
        'action' => 'set',
    ])->assertFailed();
})->group(__DIR__, __FILE__);

it('can set and retrieve special config values', function ($value): void {
    $this->artisan(ConfigCommand::class, [
        'action' => 'set',
        'key' => 'foo.bar',
        'value' => $value,
        '--file' => repository_path(ConfigManager::NAME),
    ])->assertSuccessful();

    $this->artisan(ConfigCommand::class, [
        'action' => 'get',
        'key' => 'foo.bar',
        '--file' => repository_path(ConfigManager::NAME),
    ])->assertSuccessful();
})
    ->group(__DIR__, __FILE__)
    ->with(['null', 'true', 'false', '0.0', '0', json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR)]);

it('can get config values', function (): void {
    $this->artisan(ConfigCommand::class, [
        'action' => 'get',
    ])->assertSuccessful();

    $this->artisan(ConfigCommand::class, [
        'action' => 'get',
        'key' => 'foo',
    ])->assertSuccessful();

    $this->artisan(ConfigCommand::class, [
        'action' => 'get',
        'key' => 'generators.openai',
    ])->assertSuccessful();
})->group(__DIR__, __FILE__);

it('can unset config values', function (): void {
    $this->artisan(ConfigCommand::class, [
        'action' => 'unset',
        'key' => 'foo.bar',
    ])->assertSuccessful();
})->group(__DIR__, __FILE__);

it('can reset config values', function (): void {
    $this->artisan(ConfigCommand::class, [
        'action' => 'reset',
        'key' => 'foo.bar',
    ])->assertSuccessful();

    $this->artisan(ConfigCommand::class, [
        'action' => 'reset',
    ])->assertSuccessful();
})->group(__DIR__, __FILE__);

it('can list config settings', function (): void {
    $this->artisan(ConfigCommand::class, [
        'action' => 'list',
    ])->assertSuccessful();
})->group(__DIR__, __FILE__);

it('throws ProcessFailedException for invalid editor in edit config', function (): void {
    $this->artisan(ConfigCommand::class, [
        'action' => 'edit',
        '--editor' => 'no-editor',
    ]);
})
    ->skip(windows_os(), 'GitHub Actions does not support this feature on Windows.')
    ->group(__DIR__, __FILE__)
    ->throws(ProcessFailedException::class);

it('throws ProcessFailedException when editor command is not found', function (): void {
    mockExecutableFinder('no-editor');

    $this->artisan(ConfigCommand::class, [
        'action' => 'edit',
    ]);
})
    ->skip(windows_os(), 'GitHub Actions does not support this feature on Windows.')
    ->group(__DIR__, __FILE__)
    ->throws(ProcessFailedException::class);

it('throws RuntimeException when no editor is available', function (): void {
    mockExecutableFinder(null);

    $this->artisan(ConfigCommand::class, [
        'action' => 'edit',
    ]);
})
    ->group(__DIR__, __FILE__)
    ->throws(RuntimeException::class, 'Unable to find a default editor or specify the editor.');

it('throws UnsupportedConfigActionException for unsupported action', function (): void {
    $this->artisan(ConfigCommand::class, [
        'action' => 'foo',
    ]);
})
    ->group(__DIR__, __FILE__)
    ->throws(UnsupportedConfigActionException::class, 'foo');

// Helper Functions
function mockFileExists(bool $exists): void {
    $this->getFunctionMock(class_namespace(ConfigCommand::class), 'file_exists')
        ->expects($this->atLeastOnce())
        ->willReturn($exists);
}

function mockExecutableFinder(?string $editor): void {
    app()->singleton(ExecutableFinder::class, static function () use ($editor) {
        $mock = \Mockery::mock(ExecutableFinder::class);
        $mock->allows('find')->andReturn($editor);

        return $mock;
    });
}
