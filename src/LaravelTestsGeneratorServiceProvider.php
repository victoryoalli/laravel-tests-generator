<?php

namespace Victoryoalli\LaravelTestsGenerator;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Victoryoalli\LaravelTestsGenerator\Commands\LaravelTestsGeneratorCommand;

class LaravelTestsGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-tests-generator')
            ->hasConfigFile()
            ->hasViews()
            // ->hasMigration('create_laravel-tests-generator_table')
            ->hasCommand(LaravelTestsGeneratorCommand::class);
    }
}
