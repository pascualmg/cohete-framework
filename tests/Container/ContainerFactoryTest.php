<?php

namespace Cohete\Tests\Container;

use Cohete\Bus\MessageBus;
use Cohete\Bus\ReactMessageBus;
use Cohete\Container\ContainerFactory;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class ContainerFactoryTest extends TestCase
{
    public function testCreateReturnsContainerInterface(): void
    {
        $container = ContainerFactory::create();
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testDefaultLoopInterfaceBindingExists(): void
    {
        $container = ContainerFactory::create();
        $this->assertTrue($container->has(LoopInterface::class));
        $this->assertInstanceOf(LoopInterface::class, $container->get(LoopInterface::class));
    }

    public function testDefaultLoggerInterfaceBindingExistsAndReturnsLogger(): void
    {
        $container = ContainerFactory::create();
        $this->assertTrue($container->has(LoggerInterface::class));
        $this->assertInstanceOf(Logger::class, $container->get(LoggerInterface::class));
    }

    public function testDefaultMessageBusBindingExistsAndReturnsReactMessageBus(): void
    {
        $container = ContainerFactory::create();
        $this->assertTrue($container->has(MessageBus::class));
        $this->assertInstanceOf(ReactMessageBus::class, $container->get(MessageBus::class));
    }

    public function testUserDefinitionsOverrideFrameworkDefaults(): void
    {
        $customLogger = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {}
        };

        $container = ContainerFactory::create([
            LoggerInterface::class => $customLogger,
            'custom_key' => 'custom_value'
        ]);

        $this->assertSame($customLogger, $container->get(LoggerInterface::class));
        $this->assertEquals('custom_value', $container->get('custom_key'));
    }

    public function testNamedBusesResolveToReactMessageBus(): void
    {
        $container = ContainerFactory::create();

        $this->assertInstanceOf(ReactMessageBus::class, $container->get('EventBus'));
        $this->assertInstanceOf(ReactMessageBus::class, $container->get('CommandBus'));
        $this->assertInstanceOf(ReactMessageBus::class, $container->get('QueryBus'));
    }

    public function testAutowiringWorks(): void
    {
        $container = ContainerFactory::create();

        $instance = $container->get(AutowireableClass::class);
        $this->assertInstanceOf(AutowireableClass::class, $instance);
        $this->assertInstanceOf(LoopInterface::class, $instance->loop);
    }
}

class AutowireableClass
{
    public function __construct(public LoopInterface $loop)
    {
    }
}
