<?php

namespace Cohete\Bus;

readonly class Message
{
    public function __construct(
        public string $name,
        public mixed $payload,
    ) {
    }
}
