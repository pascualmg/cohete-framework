<?php

namespace Cohete\HttpServer;

use FastRoute\Dispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Throwable;

class Kernel
{
    private ContainerInterface $container;
    private Dispatcher $dispatcher;

    public function __construct(ContainerInterface $container, string $routesPath)
    {
        $this->container = $container;
        $this->dispatcher = Router::fromJson($routesPath);
    }

    public function __invoke(ServerRequestInterface $request): PromiseInterface
    {
        return self::AsyncHandleRequest(
            request: $request,
            container: $this->container,
            dispatcher: $this->dispatcher
        )->then(
            onFulfilled: function (ResponseInterface $response): ResponseInterface {
                return $response;
            },
            onRejected: function (Throwable $exception): ResponseInterface {
                return JsonResponse::withError($exception);
            }
        );
    }

    public static function AsyncHandleRequest(
        ServerRequestInterface $request,
        ContainerInterface $container,
        Dispatcher $dispatcher
    ): PromiseInterface {
        $deferred = new Deferred();

        $method = strtoupper($request->getMethod());
        $uri = $request->getUri()->getPath();

        $routeInfo = $dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $deferred->resolve(
                    new Response(404, ['Content-Type' => 'text/plain'], 'Route not found')
                );
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = json_encode($routeInfo[1], JSON_THROW_ON_ERROR);
                $deferred->resolve(
                    new Response(405, ['Content-Type' => 'text/plain'], "Method not allowed, use  $allowedMethods ")
                );
                break;
            case Dispatcher::FOUND:
                [$_, $httpRequestHandlerName, $params] = $routeInfo;

                try {
                    $response = $container->get($httpRequestHandlerName)($request, $params);
                } catch (Throwable $throwable) {
                    $deferred->reject($throwable);
                    break;
                }

                $deferred->resolve(
                    $response instanceof PromiseInterface ? $response : self::wrapWithPromise($response)
                );
                break;
        }

        return $deferred->promise();
    }

    private static function wrapWithPromise($response): PromiseInterface
    {
        return new Promise(function ($resolve, $_) use ($response) {
            $resolve($response);
        });
    }
}
