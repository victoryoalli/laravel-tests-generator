<?php

namespace Victoryoalli\LaravelTestsGenerator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Victoryoalli\LaravelTestsGenerator\LaravelTestsGeneratorServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelTestsGeneratorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
