# cohete/framework

[![CI](https://github.com/cohete/framework/actions/workflows/ci.yml/badge.svg)](https://github.com/cohete/framework/actions/workflows/ci.yml)

Async PHP framework built on ReactPHP and RxPHP.

## Installation

```bash
composer require cohete/framework
```

## Quick Example

```php
$container = ContainerFactory::create($userDefinitions);
$kernel = new Kernel($container, __DIR__ . '/routes.json');
ReactHttpServer::init('0.0.0.0', '8080', $kernel);
```

Check out the [cohete/skeleton](https://github.com/cohete/skeleton) for a complete project structure.

## License

MIT
