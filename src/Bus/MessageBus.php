<?php

namespace Cohete\Bus;

interface MessageBus
{
    public function publish(Message $message): void;

    public function subscribe(string $messageName, callable $listener): void;
}
