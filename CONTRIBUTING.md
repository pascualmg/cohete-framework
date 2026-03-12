# Contributing to cohete/framework

## Requirements

- PHP 8.2+
- Composer

## Setup

```bash
composer install
```

## Tests

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Project Structure

```
src/
  Bus/
    Message.php             Readonly message (name + payload)
    MessageBus.php          Interface: publish() + subscribe()
    ReactMessageBus.php     Async implementation (EventEmitter + futureTick)
  Container/
    ContainerFactory.php    PHP-DI with defaults (Logger, Loop, MessageBus)
  Helper/
    ExceptionTo.php         Exception to array formatting
  HttpServer/
    Kernel.php              Route dispatch (FastRoute)
    Router.php              JSON route loading
    ReactHttpServer.php     Async HTTP server
    JsonResponse.php        JSON response helper
    HttpRequestHandler.php  Handler interface
    RequestDumper.php       Debug middleware (request logging)
    ResponseDumper.php      Debug middleware (response logging)
tests/
    Mirror of src/ structure
```

## Pull Requests

- One feature per PR
- Tests required
- PHPStan must pass at max level
