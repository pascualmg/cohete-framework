<?php

namespace Cohete\Tests;

use Cohete\Container\ContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerFactoryTest extends TestCase
{
    public function testCreateReturnsContainer(): void
    {
        $container = ContainerFactory::create();
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }
}
