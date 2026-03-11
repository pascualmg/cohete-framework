<?php

namespace Cohete\Tests\HttpServer;

use Cohete\HttpServer\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use function React\Async\await;

class KernelTest extends TestCase
{
    private string $tempRoutesFile;
    private $container;

    protected function setUp(): void
    {
        $this->tempRoutesFile = tempnam(sys_get_temp_dir(), 'routes_');
        $routes = [
            [
                'method' => 'GET',
                'path' => '/hello',
                'handler' => 'HelloHandler'
            ],
            [
                'method' => 'GET',
                'path' => '/error',
                'handler' => 'ErrorHandler'
            ]
        ];
        file_put_contents($this->tempRoutesFile, json_encode($routes));

        $this->container = $this->createMock(ContainerInterface::class);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempRoutesFile)) {
            unlink($this->tempRoutesFile);
        }
    }

    public function testInvokeReturns404ForUnknownRoute(): void
    {
        $kernel = new Kernel($this->container, $this->tempRoutesFile);
        $request = new ServerRequest('GET', 'http://localhost/unknown');

        $response = await($kernel($request));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Route not found', (string)$response->getBody());
    }

    public function testInvokeReturns405ForWrongMethod(): void
    {
        $kernel = new Kernel($this->container, $this->tempRoutesFile);
        $request = new ServerRequest('POST', 'http://localhost/hello');

        $response = await($kernel($request));

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertStringContainsString('Method not allowed', (string)$response->getBody());
    }

    public function testInvokeDispatchesToHandler(): void
    {
        $handler = function ($request, $params) {
            return new Response(200, [], 'Hello World');
        };

        $this->container->method('get')
            ->with('HelloHandler')
            ->willReturn($handler);

        $kernel = new Kernel($this->container, $this->tempRoutesFile);
        $request = new ServerRequest('GET', 'http://localhost/hello');

        $response = await($kernel($request));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello World', (string)$response->getBody());
    }

    public function testInvokeReturnsErrorResponseWhenHandlerThrows(): void
    {
        $handler = function ($request, $params) {
            throw new \Exception('Handler error');
        };

        $this->container->method('get')
            ->with('ErrorHandler')
            ->willReturn($handler);

        $kernel = new Kernel($this->container, $this->tempRoutesFile);
        $request = new ServerRequest('GET', 'http://localhost/error');

        $response = await($kernel($request));

        $this->assertEquals(418, $response->getStatusCode()); // JsonResponse::withError defaults to 418 I'm a teapot
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Handler error', $body['message']);
    }
}
