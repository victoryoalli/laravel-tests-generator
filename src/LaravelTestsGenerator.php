<?php

namespace Victoryoalli\LaravelTestsGenerator;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use OpenAI;
use OpenAI\Client;

class LaravelTestsGenerator
{
    private $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('openai.api_key'));
    }


    public function generate(string $inputFilePath, string $outputFilePath)
    {
        $testFileContent = $this->getTestFileContent($inputFilePath);

        // Create the directory if it does not exist
        $testFileDirectory = dirname($outputFilePath);
        if (!is_dir($testFileDirectory)) {
            mkdir($testFileDirectory, 0755, true);
        }

        if (file_put_contents($outputFilePath, $testFileContent) === false) {
            throw new \Exception("Failed to create the test file at {$outputFilePath}");
        }

        return $outputFilePath;
    }
    private function getTestFileContent(string $filePath)
    {
        $className = pathinfo($filePath, PATHINFO_FILENAME);
        $publicFunctions = $this->getPublicFunctions($filePath);
        $functionList = implode(', ', array_keys($publicFunctions));

        // $generatedCode = $this->completion($className, $functionList);
        $generatedCode = $this->chat($className, $functionList);

       ray($generatedCode);

        return $generatedCode;
    }

    public function completion($className, $functionList)
    {
        $response = $this->client->completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => "You are a Laravel Programmer. Create tests for the class '{$className}' which has the following methods: {$functionList}. ",
        ]);
        // ray(trim($response->choices[0]->message->content,"`"))->die();
        return $response['choices'][0]['text'];
    }

    public function chat($className, $functionList)
    {
        $prompt = "You are a Laravel Programmer. Create tests for the class '{$className}' which has the following methods: {$functionList}. ";
         $response = $this->client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
            ],
        ]);

        $generatedCode = $this->removeUnnecesaryText($response->choices[0]->message->content, "`");
        return $generatedCode;
    }

    public function removeUnnecesaryText($input)
    {
        $pattern = '/^.*?(```php)|(```).*$/';
        $replacement = '';
        $output = preg_replace($pattern, $replacement, $input);

        return $output;
    }

    private function getPublicFunctions(string $filePath)
    {
        $code = file_get_contents($filePath);
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
                                if($functionName == '__construct') {
                                    continue;
                                }
                                $params = [];

                                foreach ($method->params as $param) {
                                    $params[] = '$' . $param->var->name;
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
