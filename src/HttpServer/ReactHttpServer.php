<?php

namespace Cohete\HttpServer;

use FriendsOfReact\Http\Middleware\Psr15Adapter\PSR15Middleware;
use Middlewares\ClientIp;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

class ReactHttpServer
{
    public static function init(
        string $host,
        string $port,
        Kernel $kernel,
        ?LoopInterface $loop = null,
        ?string $staticRoot = null,
        bool $isDevelopment = false,
    ): void {
        if (null === $loop) {
            $loop = Loop::get();
        }

        $socket = new SocketServer(
            sprintf("%s:%s", $host, $port),
        );

        $clientIPMiddleware = new PSR15Middleware(
            (new ClientIp())
        );

        $requestDumperMiddleware = new PSR15Middleware(
            new RequestDumper()
        );

        $responseDumperMiddleware = new PSR15Middleware(
            new ResponseDumper()
        );

        $middlewares = [
            $clientIPMiddleware,
        ];

        if ($staticRoot !== null) {
            $middlewares[] = self::createStaticFileMiddleware($staticRoot);
        }

        $middlewares[] = $requestDumperMiddleware;
        $middlewares[] = $responseDumperMiddleware;
        $middlewares[] = $kernel;

        $httpServer = new HttpServer(...$middlewares);

        $httpServer->listen($socket);
        echo "server listening on " . $socket->getAddress();

        $httpServer->on('error', 'var_dump');
    }

    private static function createStaticFileMiddleware(string $staticRoot): callable
    {
        /** @var array<string, string> $mimeTypes */
        $mimeTypes = [
            'js' => 'application/javascript', 'css' => 'text/css',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif',
            'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
            'woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf',
            'json' => 'application/json', 'html' => 'text/html',
        ];

        return function (ServerRequestInterface $request, callable $next) use ($staticRoot, $mimeTypes) {
            $path = $request->getUri()->getPath();
            if (preg_match('/\.\w{2,5}$/', $path)) {
                $filePath = realpath($staticRoot . $path);
                $rootPath = realpath($staticRoot);
                if ($filePath !== false && $rootPath !== false && str_starts_with($filePath, $rootPath) && is_file($filePath)) {
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
                    $content = file_get_contents($filePath);
                    if ($content === false) {
                        return $next($request);
                    }
                    return new Response(200, [
                        'Content-Type' => $mime,
                        'Cache-Control' => 'public, max-age=86400',
                    ], $content);
                }
            }
            return $next($request);
        };
    }
}
