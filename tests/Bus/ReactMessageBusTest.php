<?php

namespace Cohete\Tests\Bus;

use Cohete\Bus\Message;
use Cohete\Bus\ReactMessageBus;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;

class ReactMessageBusTest extends TestCase
{
    public function testSubscribeAndPublishDeliversMessage(): void
    {
        $loop = Loop::get();
        $bus = new ReactMessageBus($loop);

        $receivedPayload = null;
        $bus->subscribe('test.message', function ($payload) use (&$receivedPayload) {
            $receivedPayload = $payload;
        });

        $bus->publish(new Message('test.message', 'hello world'));

        // Run the loop for a few ticks to process futureTicks
        $loop->futureTick(function () use ($loop) {
             $loop->stop();
        });
        $loop->run();

        // We need another run because of double futureTick in ReactMessageBus
        // publish has futureTick, and subscribe listener also has futureTick
        $loop->futureTick(function () use ($loop) {
             $loop->stop();
        });
        $loop->run();

        $this->assertEquals('hello world', $receivedPayload);
    }

    public function testPublishWithoutSubscriberDoesNotError(): void
    {
        $loop = Loop::get();
        $bus = new ReactMessageBus($loop);

        $bus->publish(new Message('no.subscriber', 'data'));

        $loop->futureTick(function () use ($loop) {
             $loop->stop();
        });
        $loop->run();

        $this->assertTrue(true); // If no error occurred, test passes
    }

    public function testMultipleSubscribersReceiveMessage(): void
    {
        $loop = Loop::get();
        $bus = new ReactMessageBus($loop);

        $count = 0;
        $bus->subscribe('multi', function ($payload) use (&$count) {
            $count++;
        });
        $bus->subscribe('multi', function ($payload) use (&$count) {
            $count++;
        });

        $bus->publish(new Message('multi', 'data'));

        // Process ticks
        for ($i = 0; $i < 3; $i++) {
            $loop->futureTick(function () use ($loop) {
                $loop->stop();
            });
            $loop->run();
        }

        $this->assertEquals(2, $count);
    }
}
