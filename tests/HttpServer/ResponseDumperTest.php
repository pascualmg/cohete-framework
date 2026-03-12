<?php

namespace Cohete\Tests\HttpServer;

use Cohete\HttpServer\ResponseDumper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResponseDumperTest extends TestCase
{
    public function testCallsHandlerAndReturnsResponse(): void
    {
        $dumper = new ResponseDumper();

        $body = $this->createMock(StreamInterface::class);
        $body->method('getSize')->willReturn(0);
        $body->method('__toString')->willReturn('');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getReasonPhrase')->willReturn('OK');
        $response->method('getHeaders')->willReturn([]);
        $response->method('getBody')->willReturn($body);

        $request = $this->createMock(ServerRequestInterface::class);

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

    public function testOutputsResponseInfo(): void
    {
        $dumper = new ResponseDumper();

        $body = $this->createMock(StreamInterface::class);
        $body->method('getSize')->willReturn(15);
        $body->method('__toString')->willReturn('{"status":"ok"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getReasonPhrase')->willReturn('Created');
        $response->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);
        $response->method('getBody')->willReturn($body);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        ob_start();
        $dumper->process($request, $handler);
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
        $this->assertStringContainsString('Created', $output);
    }
}
