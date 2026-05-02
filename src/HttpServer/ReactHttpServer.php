<?php

namespace Cohete\HttpServer;

use FriendsOfReact\Http\Middleware\Psr15Adapter\PSR15Middleware;
use Middlewares\ClientIp;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Socket\SocketServer;

class ReactHttpServer
{
    /** @var array<string, array{content: string, mime: string}> */
    private static array $fileCache = [];

    /**
     * @param array<callable> $middlewares User-provided middlewares (inserted between ClientIP and kernel)
     * @param int $maxBodySize Override del cap de buffer de body (default 64KB de React).
     *                         Si >0, mete StreamingRequestMiddleware + RequestBodyBufferMiddleware($maxBodySize)
     *                         al principio del stack para bypassear el cap automatico.
     */
    public static function init(
        string $host,
        string $port,
        Kernel $kernel,
        ?LoopInterface $loop = null,
        ?string $staticRoot = null,
        bool $isDevelopment = false,
        array $middlewares = [],
        int $maxBodySize = 0,
    ): void {
        if (null === $loop) {
            $loop = Loop::get();
        }

        $socket = new SocketServer(
            sprintf("%s:%s", $host, $port),
        );

        $stack = [];

        // Override del cap de body buffer (default React: 64KB).
        // StreamingRequestMiddleware desactiva el auto-buffer; el RequestBodyBufferMiddleware
        // explicito controla el limite real.
        if ($maxBodySize > 0) {
            $stack[] = new StreamingRequestMiddleware();
            $stack[] = new RequestBodyBufferMiddleware($maxBodySize);
        }

        $stack[] = new PSR15Middleware(new ClientIp());

        if ($staticRoot !== null) {
            $stack[] = self::createStaticFileMiddleware($staticRoot);
        }

        // User-provided middlewares
        foreach ($middlewares as $mw) {
            $stack[] = $mw;
        }

        // Dumpers only in development
        if ($isDevelopment) {
            $stack[] = new PSR15Middleware(new RequestDumper());
            $stack[] = new PSR15Middleware(new ResponseDumper());
        }

        $stack[] = $kernel;

        $httpServer = new HttpServer(...$stack);

        $httpServer->listen($socket);
        echo "server listening on " . $socket->getAddress();

        $httpServer->on('error', function (\Throwable $e) {
            fwrite(STDERR, "[HTTP Error] " . $e->getMessage() . "\n");
        });
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
                // Serve from in-memory cache if available
                if (isset(self::$fileCache[$path])) {
                    $cached = self::$fileCache[$path];
                    return new Response(200, [
                        'Content-Type' => $cached['mime'],
                        'Cache-Control' => 'public, max-age=86400',
                    ], $cached['content']);
                }

                $filePath = realpath($staticRoot . $path);
                $rootPath = realpath($staticRoot);
                if ($filePath !== false && $rootPath !== false && str_starts_with($filePath, $rootPath) && is_file($filePath)) {
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
                    $content = file_get_contents($filePath);
                    if ($content === false) {
                        return $next($request);
                    }

                    // Cache in memory for subsequent requests
                    self::$fileCache[$path] = ['content' => $content, 'mime' => $mime];

                    return new Response(200, [
                        'Content-Type' => $mime,
                        'Cache-Control' => 'public, max-age=86400',
                    ], $content);
                }
            }
            return $next($request);
        };
    }

    public static function clearFileCache(): void
    {
        self::$fileCache = [];
    }
}
