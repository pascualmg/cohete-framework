<?php

namespace Cohete\Tests\Helper;

use Cohete\Helper\ExceptionTo;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExceptionToTest extends TestCase
{
    public function testArrayReturnsExpectedKeys(): void
    {
        $exception = new RuntimeException('Test message', 456);
        $result = ExceptionTo::array($exception);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('line', $result);
        $this->assertArrayHasKey('trace', $result);

        $this->assertEquals(RuntimeException::class, $result['name']);
        $this->assertEquals(456, $result['code']);
        $this->assertEquals('Test message', $result['message']);
        $this->assertEquals($exception->getFile(), $result['file']);
        $this->assertEquals($exception->getLine(), $result['line']);
        $this->assertIsArray($result['trace']);
    }

    public function testArrayWithShortTraceReturnsShortTraceInsteadOfFullTrace(): void
    {
        $exception = new RuntimeException('Test message');
        $result = ExceptionTo::arrayWithShortTrace($exception);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('line', $result);
        $this->assertArrayHasKey('shortTrace', $result);
        $this->assertArrayNotHasKey('trace', $result);

        $this->assertIsArray($result['shortTrace']);
        $this->assertEquals($exception->getTrace()[0], $result['shortTrace']);
    }
}
