<?php

namespace Cohete\HttpServer;

use FastRoute\Dispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Promise\Deferred;
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

    /**
     * @return PromiseInterface<ResponseInterface>
     */
    public function __invoke(ServerRequestInterface $request): PromiseInterface
    {
        /** @var PromiseInterface<ResponseInterface> $promise */
        $promise = self::AsyncHandleRequest(
            request: $request,
            container: $this->container,
            dispatcher: $this->dispatcher
        );

        return $promise->then(
            onFulfilled: function (ResponseInterface $response): ResponseInterface {
                return $response;
            },
            onRejected: function (Throwable $exception): ResponseInterface {
                return JsonResponse::withError($exception);
            }
        );
    }

    /**
     * @return PromiseInterface<ResponseInterface>
     */
    public static function AsyncHandleRequest(
        ServerRequestInterface $request,
        ContainerInterface $container,
        Dispatcher $dispatcher
    ): PromiseInterface {
        /** @var Deferred<ResponseInterface> $deferred */
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
                /** @var string $httpRequestHandlerName */
                $httpRequestHandlerName = $routeInfo[1];
                /** @var array<string, string> $params */
                $params = $routeInfo[2];

                try {
                    /** @var HttpRequestHandler $handler */
                    $handler = $container->get($httpRequestHandlerName);
                    $response = $handler($request, $params);
                } catch (Throwable $throwable) {
                    $deferred->reject($throwable);
                    break;
                }

                if ($response instanceof PromiseInterface) {
                    /** @var PromiseInterface<ResponseInterface> $response */
                    $response->then(
                        fn (ResponseInterface $res) => $deferred->resolve($res),
                        fn (Throwable $e) => $deferred->reject($e)
                    );
                } else {
                    $deferred->resolve($response);
                }
                break;
        }

        return $deferred->promise();
    }
}
