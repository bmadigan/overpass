<?php

declare(strict_types=1);

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