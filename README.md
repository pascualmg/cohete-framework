# cohete/framework

[![CI](https://github.com/pascualmg/cohete-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/pascualmg/cohete-framework/actions/workflows/ci.yml)

Async PHP framework built on ReactPHP and RxPHP. ~600 LOC.

## Installation

```bash
composer require cohete/framework
```

## What's included

- **Kernel** - HTTP request dispatcher with FastRoute
- **Router** - JSON-based route definitions
- **ReactHttpServer** - Non-blocking HTTP server on ReactPHP
- **ContainerFactory** - PHP-DI container with sensible defaults
- **MessageBus** - Async event bus (EventBus, CommandBus, QueryBus)
- **JsonResponse** - Response factory for JSON APIs
- **Middleware** - Request/Response dumpers for debugging

## Quick start

```php
$container = ContainerFactory::create([
    TodoRepository::class => fn() => new InMemoryTodoRepository(),
]);

$kernel = new Kernel($container, __DIR__ . '/routes.json');

ReactHttpServer::init(
    host: '0.0.0.0',
    port: '8080',
    kernel: $kernel,
    loop: Loop::get(),
);
```

See [cohete/skeleton](https://github.com/pascualmg/cohete-skeleton) for a full working example.

## License

MIT
