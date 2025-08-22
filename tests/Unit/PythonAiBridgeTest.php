<?php

declare(strict_types=1);

use Bmadigan\Overpass\Services\PythonAiBridge;

it('can be instantiated', function () {
    $bridge = new PythonAiBridge();
    
    expect($bridge)->toBeInstanceOf(PythonAiBridge::class);
});

it('loads configuration correctly', function () {
    config(['overpass.script_path' => '/test/path/script.py']);
    config(['overpass.timeout' => 120]);
    config(['overpass.max_output_length' => 5000]);
    
    $bridge = new PythonAiBridge();
    
    // We can't directly test private properties, but we can verify the service works
    expect($bridge)->toBeInstanceOf(PythonAiBridge::class);
});

it('can be resolved from container', function () {
    $bridge = app(PythonAiBridge::class);
    
    expect($bridge)->toBeInstanceOf(PythonAiBridge::class);
});

it('can be resolved via alias', function () {
    $bridge = app('overpass');
    
    expect($bridge)->toBeInstanceOf(PythonAiBridge::class);
});

it('handles missing configuration gracefully', function () {
    config(['overpass.script_path' => null]);
    
    expect(fn () => new PythonAiBridge())->not->toThrow();
});