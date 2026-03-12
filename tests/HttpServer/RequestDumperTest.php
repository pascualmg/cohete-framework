<?php

namespace Cohete\Tests\HttpServer;

use Cohete\HttpServer\RequestDumper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestDumperTest extends TestCase
{
    public function testCallsHandlerAndReturnsResponse(): void
    {
        $dumper = new RequestDumper();

        $body = $this->createMock(StreamInterface::class);
        $body->method('getSize')->willReturn(0);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')->willReturn('/test');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('getHeaders')->willReturn([]);
        $request->method('getBody')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        ob_start();
        $result = $dumper->process($request, $handler);
        ob_end_clean();

        $this->assertSame($response, $result);
    }

    public function testOutputsRequestInfo(): void
    {
        $dumper = new RequestDumper();

        $body = $this->createMock(StreamInterface::class);
        $body->method('getSize')->willReturn(42);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')->willReturn('/todos');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getUri')->willReturn($uri);
        $request->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);
        $request->method('getBody')->willReturn($body);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->createMock(ResponseInterface::class));

        ob_start();
        $dumper->process($request, $handler);
        $output = ob_get_clean();

        $this->assertStringContainsString('POST', $output);
        $this->assertStringContainsString('/todos', $output);
        $this->assertStringContainsString('42 bytes', $output);
    }
}
