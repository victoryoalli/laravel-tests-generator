<?php

namespace Victoryoalli\LaravelTestsGenerator\Commands;

use Illuminate\Console\Command;
use Victoryoalli\LaravelTestsGenerator\LaravelTestsGenerator;

class LaravelTestsGeneratorCommand extends Command
{
    protected $signature = 'generate:test-file {inputFilePath} {outputFilePath}';

    protected $description = 'Generate a test file for the given project file path';

    private $testFileGenerator;

    public function __construct(LaravelTestsGenerator $testFileGenerator)
    {
        parent::__construct();
        $this->testFileGenerator = $testFileGenerator;
    }

    public function handle()
    {
        $inputFilePath = $this->argument('inputFilePath');
        $outputFilePath = $this->argument('outputFilePath');

        $this->testFileGenerator->generate($inputFilePath, $outputFilePath);

        $this->info("Test file generated at {$outputFilePath}");
    }
}
