# Changelog

## [0.1.0] - 2026-03-12

### Added
- `Kernel` - HTTP request routing via FastRoute with async handler dispatch
- `Router` - JSON-based route configuration
- `ReactHttpServer` - Async HTTP server on ReactPHP with PSR-15 middleware
- `ContainerFactory` - PHP-DI with sensible defaults (Logger, EventLoop, MessageBus)
- `MessageBus` interface + `ReactMessageBus` (async event emitter via futureTick)
- `JsonResponse` helper for JSON HTTP responses
- `RequestDumper` and `ResponseDumper` PSR-15 middleware for debugging
- `ExceptionTo` helper for exception formatting
- `HttpRequestHandler` interface for route handlers
- PHPUnit tests (34 tests)
- PHPStan max level analysis
- GitHub Actions CI (PHP 8.2 + 8.3)
