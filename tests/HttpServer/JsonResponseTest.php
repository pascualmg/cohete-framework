<?php

namespace Cohete\Tests\HttpServer;

use Cohete\HttpServer\JsonResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class JsonResponseTest extends TestCase
{
    public function testCreateReturnsResponseWithCorrectStatusCode(): void
    {
        $response = JsonResponse::create(201, ['foo' => 'bar']);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testCreateSetsContentTypeToApplicationJson(): void
    {
        $response = JsonResponse::create();
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
    }

    public function testWithPayloadReturns200WithJsonEncodedPayload(): void
    {
        $payload = ['foo' => 'bar'];
        $response = JsonResponse::withPayload($payload);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode($payload), (string) $response->getBody());
    }

    public function testOKReturns200WithOKString(): void
    {
        $response = JsonResponse::OK();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode('OK'), (string) $response->getBody());
    }

    public function testWithErrorReturns418ByDefaultWithExceptionDetails(): void
    {
        $exception = new RuntimeException('Test exception', 123);
        $response = JsonResponse::withError($exception);

        $this->assertEquals(418, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(RuntimeException::class, $body['name']);
        $this->assertEquals('Test exception', $body['message']);
        $this->assertEquals(123, $body['code']);
    }

    public function testWithErrorWithCustomCode(): void
    {
        $exception = new RuntimeException('Test exception');
        $response = JsonResponse::withError($exception, 500);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testNotFoundReturns404(): void
    {
        $response = JsonResponse::notFound();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(json_encode(''), (string) $response->getBody());
    }

    public function testNotFoundWithResourceNameIncludesItInBody(): void
    {
        $response = JsonResponse::notFound('User');
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(json_encode('User not found'), (string) $response->getBody());
    }

    public function testAcceptedReturns202(): void
    {
        $response = JsonResponse::accepted(['status' => 'processing']);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals(json_encode(['status' => 'processing']), (string) $response->getBody());
    }
}
