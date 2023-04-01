<?php

namespace Victoryoalli\LaravelTestsGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Victoryoalli\LaravelTestsGenerator\LaravelTestsGenerator
 */
class LaravelTestsGenerator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Victoryoalli\LaravelTestsGenerator\LaravelTestsGenerator::class;
    }
}
