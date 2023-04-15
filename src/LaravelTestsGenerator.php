<?php

namespace Victoryoalli\LaravelTestsGenerator;

use OpenAI;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class LaravelTestsGenerator
{
    private $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('tests-generator.openai.api_key'));
    }

    public function generate(string $inputFilePath, string $outputFilePath)
    {
        $testFileContent = $this->getTestFileContent($inputFilePath);

        // Create the directory if it does not exist
        $testFileDirectory = dirname($outputFilePath);
        if (! is_dir($testFileDirectory)) {
            mkdir($testFileDirectory, 0755, true);
        }

        if (file_put_contents($outputFilePath, $testFileContent) === false) {
            throw new \Exception("Failed to create the test file at {$outputFilePath}");
        }

        return $outputFilePath;
    }

    private function getTestFileContent(string $filePath)
    {
        $code = $this->getFileContents($filePath);
        $className = pathinfo($filePath, PATHINFO_FILENAME);
        $publicFunctions = $this->getPublicFunctions($filePath);
        $functionList = implode(', ', array_keys($publicFunctions));

        $generatedCode = $this->chat($className, $functionList, $code);

        return $generatedCode;
    }

    public function completion($className, $functionList)
    {
        $response = $this->client->completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => "You are a Laravel Programmer. Create tests for the class '{$className}' which has the following methods: {$functionList}. ",
        ]);

        return $response['choices'][0]['text'];
    }

    public function chat($className, $functionList, $code = null)
    {
        $prompt = "You are a Laravel Programmer. Create tests for the class '{$className}' which has the following methods: {$functionList}.";
        echo $prompt;
        $last_prompt = "\nThis is the complet code to test: {$code}";
        $response = $this->client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $prompt.$last_prompt],
            ],
        ]);
        $raw_result = $response->choices[0]->message->content;
        $generatedCode = $this->removeUnnecesaryText($raw_result, '`');

        return $generatedCode;
    }

    public function removeUnnecesaryText($input)
    {
        $pattern = '/```php(.*?)```/s';
        preg_match($pattern, $input, $matches);

        $last_match_index = count($matches) - 1;

        if (isset($matches[$last_match_index])) {
            $output = trim($matches[$last_match_index]);
        } else {
            $output = 'No PHP code block found';
        }

        return $output;
    }

    private function getFileContents(string $filePath)
    {
        if (! file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $fileContents = file_get_contents($filePath);

        if ($fileContents === false) {
            throw new \Exception("Failed to read the file: {$filePath}");
        }

        return $fileContents;
    }

    private function getPublicFunctions(string $filePath)
    {
        $code = $this->getFileContents($filePath);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        try {
            $stmts = $parser->parse($code);
            $stmts = $traverser->traverse($stmts);
        } catch (Error $e) {
            throw new \Exception("Parse error: {$e->getMessage()}");
        }

        $publicFunctions = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof \PhpParser\Node\Stmt\Namespace_) {
                foreach ($stmt->stmts as $innerStmt) {
                    if ($innerStmt instanceof \PhpParser\Node\Stmt\Class_) {
                        foreach ($innerStmt->getMethods() as $method) {
                            if ($method->isPublic()) {
                                $functionName = $method->name->toString();
                                if ($functionName == '__construct') {
                                    continue;
                                }
                                $params = [];

                                foreach ($method->params as $param) {
                                    $params[] = '$'.$param->var->name;
                                }

                                $publicFunctions[$functionName] = $params;
                            }
                        }
                    }
                }
            }
        }

        return $publicFunctions;
    }
}
