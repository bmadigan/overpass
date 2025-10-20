<?php

declare(strict_types=1);

use Bmadigan\Overpass\Tests\TestCase;
use Mockery;

uses(TestCase::class)->in(__DIR__);

afterEach(function (): void {
    Mockery::close();
});
