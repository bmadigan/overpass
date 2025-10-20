<?php

declare(strict_types=1);

use Bmadigan\Overpass\Services\PythonAiBridge;
use InvalidArgumentException;
use Mockery;

beforeEach(function () {
    config([
        'overpass.script_path' => __FILE__,
        'overpass.logging.enabled' => false,
    ]);
});

it('throws when configured script path is missing', function () {
    config(['overpass.script_path' => '/tmp/overpass-missing-script.py']);

    $bridge = new PythonAiBridge();

    $bridge->execute('health_check');
})->throws(InvalidArgumentException::class);

it('normalizes health check responses without a status key', function () {
    $bridge = Mockery::mock(PythonAiBridge::class)->makePartial();

    $bridge->shouldReceive('execute')
        ->once()
        ->with('health_check', [])
        ->andReturn([
            'success' => true,
            'data' => [
                'status' => 'healthy',
                'components' => [
                    'python' => ['status' => 'healthy'],
                ],
                'config' => [
                    'python_version' => '3.11.0',
                ],
            ],
        ]);

    $result = $bridge->testConnection();

    expect($result['status'])->toBe('healthy');
    expect($result['success'])->toBeTrue();
    expect($result['components'])->toHaveKey('python');
    expect($result['config'])->toHaveKey('python_version');
});

