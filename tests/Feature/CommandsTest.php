<?php

declare(strict_types=1);

use Bmadigan\Overpass\Services\PythonAiBridge;

it('registers install command', function () {
    expect($this->artisan('list'))
        ->toContain('overpass:install');
});

it('registers test command', function () {
    expect($this->artisan('list'))
        ->toContain('overpass:test');
});

it('install command executes without errors', function () {
    $this->artisan('overpass:install --force')
        ->assertExitCode(0);
});

it('test command handles missing python gracefully', function () {
    // Mock missing python by setting invalid script path
    config(['overpass.script_path' => '/invalid/path/script.py']);

    $this->artisan('overpass:test')
        ->assertExitCode(1)
        ->expectsOutput('❌ Connection test failed with exception!');
});

it('test command reports success when bridge returns healthy response', function () {
    app()->bind(PythonAiBridge::class, function () {
        return new class () {
            public function testConnection(): array
            {
                return [
                    'status' => 'healthy',
                    'success' => true,
                    'components' => [
                        'python' => ['status' => 'healthy'],
                    ],
                    'config' => [],
                ];
            }
        };
    });

    app()->alias(PythonAiBridge::class, 'overpass');

    $this->artisan('overpass:test')
        ->expectsOutput('🐍 Testing Overpass Bridge Connection...')
        ->expectsOutput('📋 Configuration Check:')
        ->expectsOutput('🎉 Overpass is working correctly!')
        ->assertExitCode(0);
});
