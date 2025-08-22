<?php

declare(strict_types=1);

use Bmadigan\Overpass\Services\PythonAiBridge;
use Bmadigan\Overpass\Facades\Overpass;

it('registers the service provider', function () {
    expect(app()->bound(PythonAiBridge::class))->toBeTrue();
});

it('registers the service alias', function () {
    expect(app()->bound('overpass'))->toBeTrue();
});

it('resolves the same instance when using alias', function () {
    $instance1 = app(PythonAiBridge::class);
    $instance2 = app('overpass');
    
    expect($instance1)->toBe($instance2);
});

it('loads package configuration', function () {
    expect(config('overpass'))->toBeArray();
    expect(config('overpass.timeout'))->not->toBeNull();
});

it('facade resolves correctly', function () {
    $instance = Overpass::getFacadeRoot();
    
    expect($instance)->toBeInstanceOf(PythonAiBridge::class);
});