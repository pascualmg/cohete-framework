<?php

namespace Cohete\Tests\Bus;

use Cohete\Bus\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testConstructWithStringPayload(): void
    {
        $msg = new Message('test.event', 'hello');
        $this->assertEquals('test.event', $msg->name);
        $this->assertEquals('hello', $msg->payload);
    }

    public function testConstructWithArrayPayload(): void
    {
        $payload = ['id' => '123', 'title' => 'Test'];
        $msg = new Message('domain.created', $payload);
        $this->assertEquals('domain.created', $msg->name);
        $this->assertEquals($payload, $msg->payload);
    }

    public function testConstructWithNullPayload(): void
    {
        $msg = new Message('event.empty', null);
        $this->assertEquals('event.empty', $msg->name);
        $this->assertNull($msg->payload);
    }

    public function testReadonlyProperties(): void
    {
        $msg = new Message('test', 'data');
        $this->assertEquals('test', $msg->name);
        $this->assertEquals('data', $msg->payload);
    }
}
