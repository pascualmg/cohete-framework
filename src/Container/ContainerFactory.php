<?php

namespace Cohete\Container;

use Cohete\Bus\MessageBus;
use Cohete\Bus\ReactMessageBus;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class ContainerFactory
{
    /**
     * @param array $definitions User-provided container definitions (override framework defaults)
     * @param bool $isProd Enable production optimizations (compilation, proxies)
     * @param bool $useAutowiring Enable PHP-DI autowiring
     * @param string $compilationPath Path for compiled container cache
     * @param string $proxyDirectory Path for generated proxy classes
     */
    public static function create(
        array $definitions = [],
        bool $isProd = false,
        bool $useAutowiring = true,
        string $compilationPath = __DIR__ . '/var/cache',
        string $proxyDirectory = __DIR__ . '/var/tmp'
    ): ContainerInterface {
        $builder = new ContainerBuilder();

        $builder->useAutowiring($useAutowiring);

        if ($isProd) {
            $builder->enableCompilation($compilationPath);
            $builder->writeProxiesToFile(true, $proxyDirectory);
        }

        // Framework defaults (user definitions override these)
        $frameworkDefaults = [
            LoopInterface::class => static fn () => Loop::get(),
            LoggerInterface::class => function (ContainerInterface $_) {
                $logger = new Logger('cohete');
                $logger->pushHandler(
                    new StreamHandler('php://stderr')
                );
                return $logger;
            },
            MessageBus::class => static fn (ContainerInterface $c) => $c->get(ReactMessageBus::class),
            ReactMessageBus::class => static fn (ContainerInterface $c) => new ReactMessageBus(
                $c->get(LoopInterface::class)
            ),
            'EventBus' => static fn (ContainerInterface $c) => new ReactMessageBus($c->get(LoopInterface::class)),
            'CommandBus' => static fn (ContainerInterface $c) => new ReactMessageBus($c->get(LoopInterface::class)),
            'QueryBus' => static fn (ContainerInterface $c) => new ReactMessageBus($c->get(LoopInterface::class)),
        ];

        // Framework defaults first, then user definitions (user wins)
        $builder->addDefinitions($frameworkDefaults);

        if (!empty($definitions)) {
            $builder->addDefinitions($definitions);
        }

        return $builder->build();
    }
}
