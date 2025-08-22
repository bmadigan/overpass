<?php

declare(strict_types=1);

namespace Bmadigan\Overpass\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bmadigan\Overpass\Services\PythonAiBridge
 * 
 * @method static array execute(string $operation, array $data = [])
 * @method static array analyzeData(array $data)
 * @method static array generateEmbedding(string $text)
 * @method static array vectorSearch(string $query, array $options = [])
 * @method static array chat(array $chatData)
 * @method static array testConnection()
 */
class Overpass extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bmadigan\Overpass\Services\PythonAiBridge::class;
    }
}