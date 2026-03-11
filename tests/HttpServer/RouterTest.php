<?php

namespace Cohete\Tests\HttpServer;

use Cohete\HttpServer\Router;
use FastRoute\Dispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RouterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'routes_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testFromJsonWithValidRoutes(): void
    {
        $routes = [
            [
                'method' => 'GET',
                'path' => '/test',
                'handler' => 'TestHandler'
            ]
        ];
        file_put_contents($this->tempFile, json_encode($routes));

        $dispatcher = Router::fromJson($this->tempFile);
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
    }

    public function testFromJsonWithEmptyPathThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The path of the json File to load the routes is empty');
        Router::fromJson('');
    }

    public function testFromJsonWithNonExistentFileThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The path of the json File to load dont exists');
        Router::fromJson('/path/to/non/existent/file.json');
    }

    public function testFromJsonWithInvalidJsonThrowsException(): void
    {
        file_put_contents($this->tempFile, 'invalid json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON format while loading routes from file');
        Router::fromJson($this->tempFile);
    }

    public function testToUpperWordsWithSingleMethod(): void
    {
        $result = Router::toUpperWords('get');
        $this->assertEquals(['GET'], $result);
    }

    public function testToUpperWordsWithMultipleMethods(): void
    {
        $result = Router::toUpperWords('GET,POST put');
        $this->assertEquals(['GET', 'POST', 'PUT'], $result);
    }

    public function testToUpperWordsHandlesCommaSpace(): void
    {
        $result = Router::toUpperWords('get, post');
        $this->assertEquals(['GET', 'POST'], $result);
    }
}
